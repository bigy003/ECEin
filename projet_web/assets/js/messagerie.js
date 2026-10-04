/**
 * ECE In - Messagerie + Appels Audio/Vidéo
 * Chat temps réel via polling AJAX
 * Appels via WebRTC (PeerJS)
 */

$(document).ready(function () {

    // Fonction utilitaire pour échapper le HTML
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ===================== SYSTÈME D'APPELS (WebRTC + PeerJS) =====================

    let peer = null;
    let currentCall = null;
    let localStream = null;
    let callTimerInterval = null;
    let callSeconds = 0;
    let currentCallId = null;
    let currentCallType = null;
    let isMuted = false;
    let isCameraOff = false;
    let callPollingInterval = null;
    let callStatusInterval = null;
    let ringTimeout = null;
    let audioCtx = null;

    if (typeof USER_ID !== 'undefined' && typeof Peer !== 'undefined') {
        initPeer();
        startCallPolling();
    }

    function initPeer() {
        try {
            peer = new Peer('ecein_' + USER_ID, {
                debug: 0,
                config: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                }
            });

            peer.on('open', function () {
                console.log('[ECE In] Connecté au serveur d\'appels');
            });

            peer.on('call', function (call) {
                window._incomingPeerCall = call;
            });

            peer.on('disconnected', function () {
                if (peer && !peer.destroyed) peer.reconnect();
            });

            peer.on('error', function (err) {
                console.warn('[ECE In] PeerJS:', err.type);
                if (err.type === 'peer-unavailable') {
                    afficherToast(t('L\'utilisateur n\'est pas joignable actuellement.'), 'warning');
                    terminerAppelUI();
                } else if (err.type === 'unavailable-id') {
                    setTimeout(function () {
                        if (peer) peer.destroy();
                        peer = new Peer('ecein_' + USER_ID + '_' + Date.now() % 10000, { debug: 0 });
                    }, 2000);
                }
            });
        } catch (e) {
            console.warn('[ECE In] PeerJS non disponible');
        }
    }

    function startCallPolling() {
        callPollingInterval = setInterval(function () {
            if (currentCallId) return;
            $.get('api/calls.php', { action: 'verifier' }, function (res) {
                if (res.succes && res.appel) {
                    afficherAppelEntrant(res.appel);
                }
            });
        }, 2000);
    }

    // ===== LANCER UN APPEL =====

    window.lancerAppel = function (type) {
        if (!peer || peer.disconnected) {
            afficherToast(t('Connexion au serveur d\'appels en cours, réessayez.'), 'warning');
            return;
        }
        if (!CONTACT_ID || currentCallId) return;

        currentCallType = type;
        showCallOverlay('outgoing', type);

        $.post('api/calls.php', {
            action: 'initier',
            conversation_id: CONV_ID,
            receveur_id: CONTACT_ID,
            type: type
        }, function (res) {
            if (res.succes) {
                currentCallId = res.appel_id;
                startRingtone();

                callStatusInterval = setInterval(function () {
                    $.get('api/calls.php', { action: 'statut', appel_id: currentCallId }, function (r) {
                        if (!r.succes) return;
                        if (r.statut === 'en_cours') {
                            clearInterval(callStatusInterval);
                            callStatusInterval = null;
                            stopRingtone();
                            demarrerAppelWebRTC(type, true);
                        } else if (r.statut === 'refuse' || r.statut === 'termine' || r.statut === 'manque') {
                            clearInterval(callStatusInterval);
                            callStatusInterval = null;
                            stopRingtone();
                            afficherToast(t('Appel refusé ou non répondu.'), 'info');
                            terminerAppelUI();
                        }
                    });
                }, 1500);

                setTimeout(function () {
                    if (callStatusInterval) {
                        clearInterval(callStatusInterval);
                        callStatusInterval = null;
                        if (currentCallId) {
                            $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
                            stopRingtone();
                            afficherToast(t('Pas de réponse.'), 'info');
                            terminerAppelUI();
                        }
                    }
                }, 30000);
            } else {
                afficherToast(res.message || t('Erreur lors de l\'appel.'), 'danger');
                terminerAppelUI();
            }
        });
    };

    // Fonction pour lancer un appel de groupe
    window.lancerAppelGroupeDirect = function (type, conversationId) {
        if (!peer || peer.disconnected) {
            afficherToast(t('Connexion au serveur d\'appels en cours, réessayez.'), 'warning');
            return;
        }
        if (currentCallId) return;

        currentCallType = type;
        showCallOverlay('outgoing', type);

        $.post('api/calls.php', {
            action: 'initier',
            conversation_id: conversationId,
            est_groupe: '1',
            type: type
        }, function (res) {
            if (res.succes) {
                currentCallId = res.appel_id || (res.appel_ids && res.appel_ids[0]) || 0;
                startRingtone();
                afficherToast(t('Appel de groupe lancé. Les participants sont en train d\'être appelés...'), 'info');

                // Charger immédiatement tous les participants du groupe
                chargerParticipantsAppelGroupe(conversationId);

                // Vérifier périodiquement les statuts des appels
                callStatusInterval = setInterval(function () {
                    // Récupérer tous les participants de l'appel avec leurs statuts
                    $.get('api/calls.php', { 
                        action: 'participants', 
                        appel_id: currentCallId 
                    }, function (participantsRes) {
                        if (participantsRes.succes && participantsRes.participants) {
                            // Mettre à jour les statuts des participants existants
                            // Garder tous les participants du groupe, même ceux qui n'ont pas encore répondu
                            var participantsAvecStatuts = {};
                            participantsRes.participants.forEach(function(p) {
                                participantsAvecStatuts[p.utilisateur_id] = p;
                            });
                            
                            // Mettre à jour les statuts des participants existants
                            participantsAppel.forEach(function(p) {
                                if (participantsAvecStatuts[p.utilisateur_id]) {
                                    p.statut = participantsAvecStatuts[p.utilisateur_id].statut;
                                }
                            });
                            
                            afficherParticipantsAppel();
                            
                            // Vérifier si au moins un participant a répondu
                            var auMoinsUnAccepte = participantsRes.participants.some(function(p) {
                                return p.statut === 'en_cours';
                            });
                            
                            if (auMoinsUnAccepte) {
                                stopRingtone();
                                // Pour un vrai appel de groupe, il faudrait un serveur SFU
                                // Ici on affiche juste les participants
                            }
                        }
                    });
                }, 2000);

                setTimeout(function () {
                    if (callStatusInterval) {
                        clearInterval(callStatusInterval);
                        callStatusInterval = null;
                        stopRingtone();
                    }
                }, 60000); // 60 secondes pour un appel de groupe
            } else {
                afficherToast(res.message || t('Erreur lors du lancement de l\'appel de groupe.'), 'danger');
                terminerAppelUI();
            }
        });
    };

    // Fonction pour charger tous les participants du groupe (même ceux qui n'ont pas encore répondu)
    function chargerParticipantsAppelGroupe(conversationId) {
        $.get('api/conversations.php', {
            action: 'participants_groupe',
            conversation_id: conversationId
        }, function(res) {
            if (res.succes && res.participants) {
                // Initialiser la liste avec tous les participants du groupe
                // L'utilisateur actuel sera ajouté automatiquement dans afficherParticipantsAppel
                participantsAppel = res.participants.map(function(p) {
                    return {
                        utilisateur_id: parseInt(p.id),
                        prenom: p.prenom,
                        nom: p.nom,
                        photo: p.photo,
                        statut: 'invite' // Par défaut, tous sont en attente d'invitation
                    };
                });
                // Afficher immédiatement tous les participants
                afficherParticipantsAppel();
            }
        }).fail(function() {
            // Si l'API n'existe pas, utiliser l'API participants normale
            chargerParticipantsAppel();
        });
    }

    // ===== APPEL ENTRANT =====

    function afficherAppelEntrant(appel) {
        if (currentCallId) return;
        currentCallId = appel.id;
        currentCallType = appel.type;

        $('#call-avatar').attr('src', appel.photo || 'assets/images/default_avatar.svg');
        $('#call-name').text(appel.prenom + ' ' + appel.nom);
        showCallOverlay('incoming', appel.type);
        startRingtone();
    }

    window.accepterAppel = function () {
        if (!currentCallId) return;
        stopRingtone();

        $.post('api/calls.php', { action: 'accepter', appel_id: currentCallId }, function (res) {
            if (res.succes) {
                demarrerAppelWebRTC(currentCallType, false);
            }
        });
    };

    window.refuserAppel = function () {
        if (!currentCallId) return;
        stopRingtone();
        $.post('api/calls.php', { action: 'refuser', appel_id: currentCallId });
        terminerAppelUI();
    };

    window.annulerAppel = function () {
        if (!currentCallId) return;
        stopRingtone();
        if (callStatusInterval) {
            clearInterval(callStatusInterval);
            callStatusInterval = null;
        }
        $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        terminerAppelUI();
    };

    window.terminerAppel = function () {
        if (!currentCallId) return;
        $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        if (currentCall) currentCall.close();
        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        terminerAppelUI();
    };

    // ===== WEBRTC =====

    function demarrerAppelWebRTC(type, isInitiator) {
        var constraints = { audio: true, video: type === 'video' };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (stream) {
                localStream = stream;
                showCallOverlay('active', type);
                startCallTimer();

                if (type === 'video') {
                    // Afficher dans l'ancien système (compatibilité)
                    var localVideo = document.getElementById('local-video');
                    if (localVideo) {
                        localVideo.srcObject = stream;
                        localVideo.style.display = 'block';
                    }
                    
                    // Afficher dans le nouveau système de participants
                    setTimeout(function() {
                        var localParticipantVideo = document.getElementById('local-participant-video');
                        if (localParticipantVideo) {
                            localParticipantVideo.srcObject = stream;
                            localParticipantVideo.style.display = 'block';
                            $('.call-participant[data-participant-id="' + USER_ID + '"] .call-participant-avatar').hide();
                        }
                    }, 200);
                }

                if (isInitiator) {
                    var remotePeerId = 'ecein_' + CONTACT_ID;
                    currentCall = peer.call(remotePeerId, stream);
                    if (currentCall) {
                        handleCallStream(currentCall, type);
                    } else {
                        afficherToast(t('Impossible de joindre l\'utilisateur.'), 'warning');
                        terminerAppelCleanup();
                    }
                } else {
                    if (window._incomingPeerCall) {
                        window._incomingPeerCall.answer(stream);
                        currentCall = window._incomingPeerCall;
                        handleCallStream(currentCall, type);
                        window._incomingPeerCall = null;
                    } else {
                        // Fallback: wait briefly for peer call
                        var waitCount = 0;
                        var waitPeer = setInterval(function () {
                            waitCount++;
                            if (window._incomingPeerCall) {
                                clearInterval(waitPeer);
                                window._incomingPeerCall.answer(stream);
                                currentCall = window._incomingPeerCall;
                                handleCallStream(currentCall, type);
                                window._incomingPeerCall = null;
                            } else if (waitCount > 10) {
                                clearInterval(waitPeer);
                                afficherToast(t('Connexion audio/vidéo en attente...'), 'info');
                            }
                        }, 500);
                    }
                }
            })
            .catch(function (err) {
                console.error('[ECE In] Erreur média:', err);
                afficherToast(t('Impossible d\'accéder au micro/caméra. Vérifiez les permissions du navigateur.'), 'danger');
                if (currentCallId) {
                    $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
                }
                terminerAppelUI();
            });
    }

    function handleCallStream(call, type) {
        call.on('stream', function (remoteStream) {
            // Trouver le participant correspondant
            var remotePeerId = call.peer;
            var participantId = remotePeerId.replace('ecein_', '');
            var $participant = $('.call-participant[data-participant-id="' + participantId + '"]');
            
            if ($participant.length > 0) {
                // Afficher la vidéo dans le conteneur du participant
                var $video = $participant.find('.remote-participant-video[data-peer-id="' + remotePeerId + '"]');
                if ($video.length > 0) {
                    $video[0].srcObject = remoteStream;
                    $video.show();
                    $participant.find('.call-participant-avatar').hide();
                }
            }
            
            // Fallback pour l'ancien système (compatibilité)
            if (type === 'video') {
                var remoteVideo = document.getElementById('remote-video');
                if (remoteVideo && !remoteVideo.srcObject) {
                    remoteVideo.srcObject = remoteStream;
                    remoteVideo.style.display = 'block';
                    $('#call-info').addClass('call-info-mini');
                }
            }
            
            // Audio pour tous les cas
            var audioEl = document.getElementById('remote-audio-' + participantId);
            if (!audioEl) {
                audioEl = document.createElement('audio');
                audioEl.id = 'remote-audio-' + participantId;
                audioEl.autoplay = true;
                document.body.appendChild(audioEl);
            }
            audioEl.srcObject = remoteStream;
        });

        call.on('close', function () {
            afficherToast(t('Appel terminé.'), 'info');
            terminerAppelCleanup();
        });

        call.on('error', function () {
            afficherToast(t('Erreur durant l\'appel.'), 'danger');
            terminerAppelCleanup();
        });
    }

    function terminerAppelCleanup() {
        if (currentCallId) {
            $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        }
        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        terminerAppelUI();
    }

    // ===== CONTRÔLES D'APPEL =====

    window.toggleMute = function () {
        if (!localStream) return;
        isMuted = !isMuted;
        localStream.getAudioTracks().forEach(function (t) { t.enabled = !isMuted; });
        var $btn = $('#btn-mute');
        $btn.toggleClass('active', isMuted);
        $btn.find('i').attr('class', isMuted ? 'bi bi-mic-mute-fill' : 'bi bi-mic-fill');
    };

    window.toggleCamera = function () {
        if (!localStream) return;
        isCameraOff = !isCameraOff;
        localStream.getVideoTracks().forEach(function (t) { t.enabled = !isCameraOff; });
        var $btn = $('#btn-camera');
        $btn.toggleClass('active', isCameraOff);
        $btn.find('i').attr('class', isCameraOff ? 'bi bi-camera-video-off-fill' : 'bi bi-camera-video-fill');
        document.getElementById('local-video').style.display = isCameraOff ? 'none' : 'block';
    };

    // ===== UI APPELS =====

    function showCallOverlay(mode, type) {
        var $overlay = $('#call-overlay');
        $overlay.fadeIn(200);

        // Réinitialiser l'affichage de l'info d'appel
        $('#call-info').show();
        
        if (mode !== 'incoming' && typeof CONTACT_NAME !== 'undefined') {
            $('#call-avatar').attr('src', CONTACT_PHOTO || 'assets/images/default_avatar.svg');
            $('#call-name').text(CONTACT_NAME);
        }

        $('#call-controls-active, #call-controls-incoming, #call-controls-outgoing').hide();

        if (mode === 'incoming') {
            $('#call-status').text(type === 'video' ? t('Appel vidéo entrant...') : t('Appel audio entrant...'));
            $('#call-controls-incoming').show();
            $overlay.addClass('call-ringing');
        } else if (mode === 'outgoing') {
            $('#call-status').text(t('Appel en cours...'));
            $('#call-controls-outgoing').show();
            $overlay.removeClass('call-ringing');
            // Masquer le bouton "Ajouter une personne" pour les appels de groupe
            if (typeof IS_GROUP !== 'undefined' && IS_GROUP) {
                $('#btn-add-participant-outgoing').hide();
            } else {
                $('#btn-add-participant-outgoing').show();
                chargerParticipantsAppel();
            }
        } else if (mode === 'active') {
            $('#call-status').text(t('En communication'));
            $('#call-timer').show();
            $('#call-controls-active').show();
            $overlay.removeClass('call-ringing');
            // Masquer le bouton "Ajouter une personne" pour les appels de groupe
            if (typeof IS_GROUP !== 'undefined' && IS_GROUP) {
                $('#btn-add-participant').hide();
            } else {
                $('#btn-add-participant').show();
            }
            if (type === 'video') {
                $('#btn-camera').show();
            }
            // Charger et afficher les participants
            chargerParticipantsAppel();
            demarrerRafraichissementParticipants();
        }
    }

    function terminerAppelUI() {
        stopCallTimer();
        stopRingtone();
        arreterRafraichissementParticipants();
        $('#call-participants-container').empty();
        participantsAppel = [];

        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        if (currentCall) {
            currentCall.close();
            currentCall = null;
        }

        currentCallId = null;
        currentCallType = null;
        isMuted = false;
        isCameraOff = false;

        if (callStatusInterval) {
            clearInterval(callStatusInterval);
            callStatusInterval = null;
        }

        var $overlay = $('#call-overlay');
        $overlay.fadeOut(200).removeClass('call-ringing');
        $('#call-timer').hide().text('00:00');
        $('#call-status').text('');
        // Réinitialiser l'affichage de l'info d'appel pour le prochain appel
        $('#call-info').show();
        $('#call-participants-container').empty();
        $('#remote-video, #local-video').hide();

        var rv = document.getElementById('remote-video');
        var lv = document.getElementById('local-video');
        if (rv) rv.srcObject = null;
        if (lv) lv.srcObject = null;

        $('#call-info').removeClass('call-info-mini');
        $('#btn-mute').removeClass('active').find('i').attr('class', 'bi bi-mic-fill');
        $('#btn-camera').removeClass('active').hide().find('i').attr('class', 'bi bi-camera-video-fill');

        var remoteAudio = document.getElementById('remote-audio');
        if (remoteAudio) remoteAudio.remove();
    }

    // ===== TIMER =====

    // ===== AJOUTER UN PARTICIPANT À L'APPEL =====
    
    // Fonction globale pour ouvrir la modal d'ajout de participant
    window.ouvrirModalAjouterParticipant = function() {
        
        if (!currentCallId) {
            afficherToast(t('Aucun appel en cours.'), 'warning');
            return;
        }
        
        // Charger les participants de l'appel d'abord
        $.get('api/calls.php', {
            action: 'participants',
            appel_id: currentCallId
        }, function(res) {
            if (!res.succes) {
                afficherToast(t('Erreur lors du chargement des participants.'), 'danger');
                return;
            }
            
            // Récupérer la liste des IDs des participants déjà dans l'appel
            var participantsIds = [];
            if (res.participants && res.participants.length > 0) {
                res.participants.forEach(function(p) {
                    participantsIds.push(parseInt(p.utilisateur_id));
                });
            }
            // Ajouter aussi soi-même
            if (typeof USER_ID !== 'undefined' && USER_ID) {
                participantsIds.push(parseInt(USER_ID));
            }
            
            // Charger la liste des contacts disponibles
            var html = '';
            var contactsDisponibles = 0;
            
            if (typeof CONTACTS_APPEL !== 'undefined' && CONTACTS_APPEL.length > 0) {
                CONTACTS_APPEL.forEach(function(contact) {
                    var contactId = parseInt(contact.id);
                    // Exclure tous les participants déjà dans l'appel (y compris soi-même)
                    if (participantsIds.indexOf(contactId) !== -1) {
                        return;
                    }
                    contactsDisponibles++;
                    html += '<div class="d-flex align-items-center gap-2 p-2 hover-bg rounded-3 mb-2" style="cursor:pointer" onclick="ajouterParticipantAppel(' + contact.id + ', \'' + escapeHtml(contact.prenom + ' ' + contact.nom) + '\')">';
                    html += '<img src="' + escapeHtml(contact.photo || 'assets/images/default_avatar.svg') + '" alt="" class="rounded-circle" width="40" height="40" style="object-fit:cover">';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="fw-semibold small">' + escapeHtml(contact.prenom + ' ' + contact.nom) + '</div>';
                    html += '<div style="font-size:.72rem;color:var(--text-3)">' + escapeHtml(contact.titre || '') + '</div>';
                    html += '</div>';
                    html += '<i class="bi bi-plus-circle" style="color:var(--accent); font-size:1.2rem;"></i>';
                    html += '</div>';
                });
            }
            
            if (contactsDisponibles === 0) {
                html = '<p class="text-center text-muted py-4">' + t('Aucun contact disponible à ajouter.') + '</p>';
            }
            
            $('#liste-contacts-appel').html(html);
            
            // Recherche
            $('#search-participant').off('input').on('input', function() {
                var search = $(this).val().toLowerCase();
                $('#liste-contacts-appel > div').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(search));
                });
            });
            
            // Réinitialiser le champ de recherche
            $('#search-participant').val('');
            
            // Ouvrir la modal avec Bootstrap
            var modalElement = document.getElementById('modalAjouterParticipant');
        if (!modalElement) {
            console.error('Modal modalAjouterParticipant introuvable');
            afficherToast(t('Erreur : fenêtre introuvable'), 'danger');
            return;
        }
        
        // Forcer le z-index et la visibilité avant d'ouvrir
        $(modalElement).css({
            'z-index': '10000',
            'display': 'block'
        });
        
        // Utiliser Bootstrap 5
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalElement, {
                backdrop: true,
                keyboard: true
            });
            
            // Écouter l'événement show pour forcer le z-index
            modalElement.addEventListener('shown.bs.modal', function() {
                $(modalElement).css('z-index', '10000');
                $('.modal-backdrop').each(function() {
                    if ($(this).css('z-index') < 10000) {
                        $(this).css('z-index', '9998');
                    }
                });
            }, { once: true });
            
            modal.show();
            
            // Forcer le z-index après un court délai
            setTimeout(function() {
                $(modalElement).css('z-index', '10000');
                $('.modal-backdrop').each(function() {
                    var zIndex = parseInt($(this).css('z-index')) || 1050;
                    if (zIndex >= 10000) {
                        $(this).css('z-index', '9998');
                    }
                });
            }, 50);
        } else {
            // Fallback pour Bootstrap 4 ou jQuery
            $(modalElement).css('z-index', '10000');
            $(modalElement).modal({
                backdrop: true,
                keyboard: true
            });
            $(modalElement).modal('show');
            
            setTimeout(function() {
                $(modalElement).css('z-index', '10000');
                $('.modal-backdrop').css('z-index', '9998');
            }, 50);
        }
        }).fail(function() {
            afficherToast(t('Erreur lors du chargement des participants.'), 'danger');
        });
    };
    
    window.ajouterParticipantAppel = function(participantId, participantName) {
        if (!currentCallId) return;
        
        $.post('api/calls.php', {
            action: 'ajouter_participant',
            appel_id: currentCallId,
            participant_id: participantId
        }, function(res) {
            if (res.succes) {
                afficherToast(t('%s a été invité à l\'appel.', participantName), 'success');
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalAjouterParticipant'));
                if (modal) modal.hide();
                
                // Rafraîchir l'affichage des participants
                chargerParticipantsAppel();
            } else {
                afficherToast(res.message || t('Erreur lors de l\'ajout.'), 'danger');
            }
        }).fail(function() {
            afficherToast(t('Erreur lors de l\'ajout du participant.'), 'danger');
        });
    };
    
    // ===== GESTION DES PARTICIPANTS DE L'APPEL =====
    
    let participantsAppel = [];
    let participantsInterval = null;
    
    function chargerParticipantsAppel() {
        if (!currentCallId) return;
        
        $.get('api/calls.php', {
            action: 'participants',
            appel_id: currentCallId
        }, function(res) {
            if (res.succes && res.participants) {
                participantsAppel = res.participants;
                afficherParticipantsAppel();
            }
        });
    }
    
    function afficherParticipantsAppel() {
        var $container = $('#call-participants-container');
        $container.empty();
        
        // Ajouter l'utilisateur actuel (local)
        var html = '';
        
        // Participant local (vous)
        html += '<div class="call-participant" data-participant-id="' + USER_ID + '">';
        html += '<video id="local-participant-video" autoplay playsinline muted class="call-participant-video" style="display:none;"></video>';
        html += '<img src="' + escapeHtml((typeof USER_PHOTO !== 'undefined' ? USER_PHOTO : 'assets/images/default_avatar.svg')) + '" alt="" class="call-participant-avatar">';
        html += '<div class="call-participant-info">';
        html += '<div class="call-participant-name">' + t('Vous') + '</div>';
        html += '<div class="call-participant-status">' + t('En ligne') + '</div>';
        html += '</div>';
        html += '</div>';
        
        // Ajouter les autres participants
        participantsAppel.forEach(function(participant) {
            if (participant.utilisateur_id == USER_ID) return; // Déjà ajouté
            
            html += '<div class="call-participant" data-participant-id="' + participant.utilisateur_id + '">';
            html += '<video class="call-participant-video remote-participant-video" autoplay playsinline style="display:none;" data-peer-id="ecein_' + participant.utilisateur_id + '"></video>';
            html += '<img src="' + escapeHtml(participant.photo || 'assets/images/default_avatar.svg') + '" alt="" class="call-participant-avatar">';
            html += '<div class="call-participant-info">';
            html += '<div class="call-participant-name">' + escapeHtml(participant.prenom + ' ' + participant.nom) + '</div>';
            html += '<div class="call-participant-status">';
            if (participant.statut === 'invite') {
                html += t('Invitation envoyée');
            } else if (participant.statut === 'en_cours') {
                html += t('En ligne');
            } else if (participant.statut === 'refuse') {
                html += t('Refusé');
            } else {
                html += t('En attente');
            }
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        
        $container.html(html);
        
        // Appliquer la classe pour le layout selon le nombre de participants
        var totalParticipants = participantsAppel.length + 1; // +1 pour l'utilisateur local
        $container.removeClass('participants-2 participants-3 participants-4 participants-5 participants-6');
        if (totalParticipants >= 2) {
            $container.addClass('participants-' + Math.min(totalParticipants, 6));
        }
        
        // Si l'appel est actif et qu'on a un stream local, l'afficher
        if (localStream && currentCallType === 'video') {
            setTimeout(function() {
                var localVideo = document.getElementById('local-participant-video');
                if (localVideo) {
                    localVideo.srcObject = localStream;
                    localVideo.style.display = 'block';
                    $container.find('[data-participant-id="' + USER_ID + '"] .call-participant-avatar').hide();
                }
            }, 100);
        }
        
        // Masquer l'info d'appel si on a des participants
        if (totalParticipants > 1) {
            $('#call-info').hide();
        } else {
            $('#call-info').show();
        }
    }
    
    // Rafraîchir la liste des participants périodiquement pendant l'appel
    function demarrerRafraichissementParticipants() {
        if (participantsInterval) {
            clearInterval(participantsInterval);
        }
        participantsInterval = setInterval(function() {
            if (currentCallId) {
                chargerParticipantsAppel();
            } else {
                clearInterval(participantsInterval);
                participantsInterval = null;
            }
        }, 3000); // Toutes les 3 secondes
    }
    
    function arreterRafraichissementParticipants() {
        if (participantsInterval) {
            clearInterval(participantsInterval);
            participantsInterval = null;
        }
    }

    function startCallTimer() {
        callSeconds = 0;
        updateTimerDisplay();
        callTimerInterval = setInterval(function () {
            callSeconds++;
            updateTimerDisplay();
        }, 1000);
    }

    function stopCallTimer() {
        if (callTimerInterval) {
            clearInterval(callTimerInterval);
            callTimerInterval = null;
        }
        callSeconds = 0;
    }

    function updateTimerDisplay() {
        var m = String(Math.floor(callSeconds / 60)).padStart(2, '0');
        var s = String(callSeconds % 60).padStart(2, '0');
        $('#call-timer').text(m + ':' + s);
    }

    // ===== SONNERIE =====

    function startRingtone() {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            playRingTone();
        } catch (e) {
            // Audio API not available
        }
    }

    function playRingTone() {
        if (!audioCtx || audioCtx.state === 'closed') return;

        var now = audioCtx.currentTime;

        function beep(startTime, freq, duration) {
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.12, startTime);
            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
            osc.start(startTime);
            osc.stop(startTime + duration);
        }

        beep(now, 440, 0.25);
        beep(now + 0.35, 520, 0.25);

        ringTimeout = setTimeout(function () {
            if (audioCtx && currentCallId) {
                playRingTone();
            }
        }, 2000);
    }

    function stopRingtone() {
        if (ringTimeout) {
            clearTimeout(ringTimeout);
            ringTimeout = null;
        }
        if (audioCtx) {
            try { audioCtx.close(); } catch (e) {}
            audioCtx = null;
        }
    }

    // ===================== MESSAGERIE (CHAT) =====================

    if (typeof CONV_ID === 'undefined' || CONV_ID === 0) return;

    scrollBas();

    var dernierMsgId = getDernierMsgId();

    // ===== ENVOI ET MODIFICATION DE MESSAGES =====

    let editMode = false;
    let editMsgId = null;

    window.editerMessage = function(msgId) {
        var $msgWrapper = $('.message-wrapper[data-msg-id="' + msgId + '"]');
        var dateEnvoi = parseInt($msgWrapper.attr('data-date'));
        var now = Math.floor(Date.now() / 1000);
        
        if (now - dateEnvoi > 30) {
            afficherToast(t('Le délai de 30 secondes pour modifier ce message est écoulé.'), 'warning');
            $msgWrapper.find('.btn-edit-msg').hide();
            return;
        }

        var text = $msgWrapper.find('.msg-text').text();
        $('#input-message').val(text).focus();
        
        editMode = true;
        editMsgId = msgId;
        
        $('#input-message').addClass('editing-mode');
        $('#btn-envoyer').html('<i class="bi bi-check-lg"></i>');
        
        // Hide file attach button during edit
        $('#btn-piece-jointe').hide();
    };

    window.supprimerMessage = function(msgId) {
        if (!confirm(t('Voulez-vous vraiment supprimer ce message ?'))) return;
        
        $.post('api/messages.php', {
            action: 'supprimer',
            message_id: msgId
        }, function(res) {
            if (res.succes) {
                var $msg = $('#msg-content-' + msgId);
                $msg.addClass('fst-italic text-muted');
                $msg.html('<div><i class="bi bi-slash-circle me-1"></i> ' + t('Ce message a été supprimé.') + '</div>');
                $('.message-wrapper[data-msg-id="' + msgId + '"] .message-actions').remove();
            } else {
                afficherToast(res.message, 'danger');
            }
        });
    };

    window.annulerEdit = function() {
        editMode = false;
        editMsgId = null;
        $('#input-message').val('').removeClass('editing-mode');
        $('#btn-envoyer').html('<i class="bi bi-send-fill"></i>');
        $('#btn-piece-jointe').show();
    };

    $('#formMessage').on('submit', function (e) {
        e.preventDefault();
        var $input  = $('#input-message');
        var $fileInput = $('#input-fichier');
        var contenu = $input.val().trim();
        var hasFile = $fileInput.length > 0 && $fileInput[0].files.length > 0;
        
        if (!contenu && !hasFile) return;

        var $btn = $('#btn-envoyer');
        $btn.prop('disabled', true);

        if (editMode && editMsgId) {
            $.post('api/messages.php', {
                action: 'modifier',
                message_id: editMsgId,
                contenu: contenu
            }, function(res) {
                if (res.succes) {
                    var $msgContent = $('#msg-content-' + editMsgId);
                    $msgContent.find('.msg-text').html(escapeHtml(res.nouveau_contenu).replace(/\n/g, '<br>'));
                    if ($msgContent.find('small.text-white-50').length === 0) {
                        $msgContent.append('<small class="text-white-50" style="font-size: 0.65rem;">(' + t('modifié') + ')</small>');
                    }
                    annulerEdit();
                } else {
                    afficherToast(res.message, 'danger');
                }
            }).always(function() {
                $btn.prop('disabled', false);
            });
            return;
        }

        var formData = new FormData(this);
        formData.set('action', 'envoyer');

        $.ajax({
            url: 'api/messages.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.succes) {
                    $input.val('');
                    if (typeof clearFile === 'function') clearFile();
                    ajouterMessage(res.message);
                    scrollBas();
                    dernierMsgId = parseInt(res.message.id);
                } else {
                    afficherToast(res.message, 'danger');
                }
            },
            error: function () {
                afficherToast(t('Erreur lors de l\'envoi du message.'), 'danger');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $input.focus();
            }
        });
    });

    $('#input-message').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#formMessage').submit();
        } else if (e.key === 'Escape' && editMode) {
            e.preventDefault();
            annulerEdit();
        }
    });

    // ===== POLLING (nouveaux messages) =====

    var intervalPolling = setInterval(function () {
        if (!CONV_ID) return;
        $.get('api/messages.php', {
            action:          'polling',
            conversation_id: CONV_ID,
            dernier_id:      dernierMsgId,
        }, function (res) {
            if (res.succes && res.messages.length > 0) {
                res.messages.forEach(function (msg) {
                    if (msg.expediteur_id != USER_ID) {
                        ajouterMessage(msg);
                        // Marquer le message comme lu
                        marquerMessageLu(msg.id);
                    } else {
                        // Mettre à jour le statut des messages envoyés
                        mettreAJourStatutMessage(msg.id, msg.distribue, msg.lu);
                    }
                    dernierMsgId = Math.max(dernierMsgId, parseInt(msg.id));
                });
                scrollBas();
            }
        });
    }, 3000);
    
    // Fonction pour marquer un message comme lu
    function marquerMessageLu(msgId) {
        $.post('api/messages.php', {
            action: 'marquer_lu',
            message_id: msgId
        }, function(res) {
            // Optionnel : mettre à jour l'interface si nécessaire
        });
    }
    
    // Fonction pour mettre à jour le statut d'un message (distribué/lu)
    function mettreAJourStatutMessage(msgId, distribue, lu) {
        var $msgWrapper = $('.message-wrapper[data-msg-id="' + msgId + '"]');
        if ($msgWrapper.length === 0) return;
        
        var $statusIcon = $msgWrapper.find('.message-status i');
        if ($statusIcon.length === 0) return;
        
        distribue = distribue || 0;
        lu = lu || 0;
        
        if (lu == 1) {
            $statusIcon.attr('class', 'bi bi-check2-all').css('color', '#4FC3F7');
        } else if (distribue == 1) {
            $statusIcon.attr('class', 'bi bi-check2-all').css('color', 'var(--text-3)');
        } else {
            $statusIcon.attr('class', 'bi bi-check2').css('color', 'var(--text-3)');
        }
    }
    
    // Marquer tous les messages comme lus quand la conversation est ouverte
    if (typeof CONV_ID !== 'undefined' && CONV_ID > 0) {
        $.post('api/messages.php', {
            action: 'marquer_tous_lus',
            conversation_id: CONV_ID
        });
    }

    $(window).on('beforeunload', function () {
        clearInterval(intervalPolling);
        if (callPollingInterval) clearInterval(callPollingInterval);
        if (currentCallId) {
            navigator.sendBeacon('api/calls.php', new URLSearchParams({
                action: 'terminer',
                appel_id: currentCallId
            }));
        }
    });

    // ===== FONCTIONS UTILITAIRES CHAT =====

    function ajouterMessage(msg) {
        var estMoi = msg.expediteur_id == USER_ID;
        var dateTs = Math.floor(new Date(msg.date_envoi).getTime() / 1000);
        
        var html = '<div class="message-wrapper ' + (estMoi ? 'message-moi' : 'message-autre') + ' mb-3" data-msg-id="' + msg.id + '" data-date="' + dateTs + '">';

        if (!estMoi) {
            html += '<img src="' + escapeHtml(msg.avatar) + '" alt="" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover">';
        }

        if (estMoi) {
            html += '<div class="message-actions me-2 d-flex flex-column gap-1" style="opacity: 0; transition: opacity 0.2s;">' +
                    '<button class="btn btn-sm btn-light py-0 px-1 btn-edit-msg" title="' + t('Modifier') + '" onclick="editerMessage(' + msg.id + ')"><i class="bi bi-pencil" style="font-size:0.75rem;"></i></button>' +
                    '<button class="btn btn-sm btn-light py-0 px-1 text-danger btn-delete-msg" title="' + t('Supprimer') + '" onclick="supprimerMessage(' + msg.id + ')"><i class="bi bi-trash" style="font-size:0.75rem;"></i></button>' +
                    '</div>';
        }

        html += '<div class="message-bulle ' + (estMoi ? 'message-bubble-moi' : 'message-bubble-autre') + '" id="msg-content-' + msg.id + '">';
        
        if (msg.fichier_joint) {
            var ext = msg.fichier_joint.split('.').pop().toLowerCase();
            var imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            html += '<div class="mb-2">';
            if (imgExts.includes(ext)) {
                html += '<a href="' + escapeHtml(msg.fichier_joint) + '" target="_blank"><img src="' + escapeHtml(msg.fichier_joint) + '" alt="' + t('Pièce jointe') + '" class="img-fluid rounded" style="max-height: 200px;"></a>';
            } else {
                html += '<a href="' + escapeHtml(msg.fichier_joint) + '" target="_blank" class="text-decoration-none d-flex align-items-center gap-2 p-2 bg-light rounded text-dark" style="background: rgba(255,255,255,0.2) !important;">' +
                        '<i class="bi bi-file-earmark-text fs-4"></i>' +
                        '<span class="small text-truncate" style="max-width: 150px;">' + escapeHtml(msg.fichier_joint.split('/').pop()) + '</span>' +
                        '</a>';
            }
            html += '</div>';
        }
        
        if (msg.contenu) {
            html += '<div class="msg-text">' + escapeHtml(msg.contenu).replace(/\n/g, '<br>') + '</div>';
        }
        
        // Ajouter les icônes de statut pour les messages envoyés
        // Par défaut, un message envoyé est toujours distribué (sauf si la colonne n'existe pas)
        var distribue = msg.distribue !== undefined ? msg.distribue : 1;
        var lu = msg.lu || 0;
        var statusIcon = '';
        if (estMoi) {
            if (lu == 1) {
                // Message lu : double coche bleue
                statusIcon = '<i class="bi bi-check2-all" style="color: #4FC3F7;"></i>';
            } else if (distribue == 1) {
                // Message distribué : double coche grise
                statusIcon = '<i class="bi bi-check2-all" style="color: var(--text-3);"></i>';
            } else {
                // Message envoyé mais pas encore distribué : simple coche grise
                statusIcon = '<i class="bi bi-check2" style="color: var(--text-3);"></i>';
            }
        }
        
        html += '<div class="d-flex align-items-center gap-1" style="justify-content: ' + (estMoi ? 'flex-end' : 'flex-start') + ';">';
        html += '<div class="message-time">' + tempsEcoule(msg.date_envoi) + '</div>';
        if (statusIcon) {
            html += '<span class="message-status" style="font-size: 0.75rem; margin-left: 4px;">' + statusIcon + '</span>';
        }
        html += '</div>';
        html += '</div>';

        if (estMoi) {
            html += '<img src="' + escapeHtml(msg.avatar) + '" alt="" class="rounded-circle ms-2" width="32" height="32" style="object-fit:cover">';
        }

        html += '</div>';

        var $chatMessages = $('#chat-messages');
        $chatMessages.find('.text-muted').closest('div.text-center').remove();
        $chatMessages.append(html);
    }

    function scrollBas() {
        var el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    function getDernierMsgId() {
        var maxId = 0;
        document.querySelectorAll('#chat-messages .message-wrapper[data-msg-id]').forEach(function (el) {
            var id = parseInt(el.getAttribute('data-msg-id'));
            if (id > maxId) maxId = id;
        });
        return maxId;
    }

});
