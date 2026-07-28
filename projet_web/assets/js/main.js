/**
 * ECE In - Script JavaScript principal
 * Utilise jQuery 3.7.1 + Bootstrap 5
 * Projet Web ING2 2026
 */

$(document).ready(function () {

    // ===================== GESTION DES PUBLICATIONS =====================

    /**
     * Afficher/masquer la zone d'upload selon le type de publication
     */
    $('input[name="type_post"]').on('change', function () {
        const type = $(this).val();
        const zoneUpload = $('#zone-upload');
        if (type === 'photo' || type === 'video' || type === 'cv') {
            zoneUpload.slideDown(200);
            $('#input-fichier').attr('accept', getAcceptType(type));
        } else {
            zoneUpload.slideUp(200);
        }
        $('#post-type').val(type);
    });

    function getAcceptType(type) {
        if (type === 'photo')  return 'image/jpeg,image/png,image/gif,image/webp';
        if (type === 'video')  return 'video/mp4,video/webm,video/ogg';
        if (type === 'cv')     return 'application/pdf,.xml';
        return '';
    }

    // Ouvrir le bon type si on clique sur les boutons de la zone de publication
    $('[data-bs-target="#modalPublier"][data-type]').on('click', function () {
        const type = $(this).data('type');
        $(`#type-${type}`).prop('checked', true).trigger('change');
    });

    // ===================== ENVOI DU FORMULAIRE DE PUBLICATION =====================

    $('#formPublier').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $('#btn-publier');
        const $form = $(this);
        const $modal = $('#modalPublier');
        
        // Vérifier que le bouton n'est pas déjà désactivé (protection double-clic)
        if ($btn.prop('disabled')) {
            console.warn('Bouton déjà désactivé, soumission ignorée');
            return false;
        }
        
        const formData = new FormData(this);
        formData.set('action', 'publier');
        formData.set('type', $('input[name="type_post"]:checked').val() || 'statut');

        // Désactiver le bouton et afficher le spinner
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Publication...');
        
        // S'ASSURER QUE LE MODAL RESTE INTERACTIF
        // Forcer le z-index du modal pour qu'il soit au-dessus de tout
        $modal.css('z-index', '1060');
        $modal.find('.modal-dialog').css('z-index', '1061');
        $modal.find('.modal-content').css('z-index', '1062');
        
        // S'assurer que le backdrop ne bloque pas
        $modal.siblings('.modal-backdrop').css('z-index', '1055');

        $.ajax({
            url:         'api/posts.php',
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            timeout:     30000,
            success: function (res) {
                console.log('Réponse serveur:', res);
                if (res && res.succes) {
                    // Fermer le modal
                    try {
                        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalPublier'));
                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            $modal.modal('hide');
                        }
                    } catch (err) {
                        console.error('Erreur fermeture modal:', err);
                        $modal.modal('hide');
                    }
                    // Afficher message de succès
                    afficherToast('Publication créée avec succès !', 'success');
                    // Recharger la page pour afficher le post
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    afficherToast(res?.message || 'Erreur lors de la publication.', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erreur AJAX:', {status, error, xhr});
                let message = 'Erreur lors de la publication.';
                if (status === 'timeout') {
                    message = 'La requête a pris trop de temps. Veuillez réessayer.';
                } else if (xhr.status === 0) {
                    message = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                }
                afficherToast(message, 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier');
            },
            complete: function () {
                // S'assurer que le bouton est réactivé même en cas d'erreur
                setTimeout(function() {
                    if ($btn.prop('disabled')) {
                        console.warn('Bouton toujours désactivé, réactivation forcée');
                        $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier');
                    }
                }, 1000);
            },
        });
        
        return false;
    });
    
    // SÉCURITÉ : Réactiver le formulaire quand le modal se ferme
    $('#modalPublier').on('hidden.bs.modal', function() {
        const $btn = $('#btn-publier');
        $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier');
        $('#formPublier')[0].reset();
    });

    // ===================== APERÇU D'IMAGE AVANT UPLOAD =====================

    $('#input-fichier').on('change', function () {
        const file = this.files[0];
        const preview = $('#preview-fichier');
        preview.empty();

        if (!file) return;

        let btnSupprimer = `<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle shadow" id="btn-supprimer-media" title="Supprimer"><i class="bi bi-x"></i></button>`;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.html(`
                    <div class="position-relative d-inline-block">
                        <img src="${e.target.result}" class="img-fluid rounded shadow-sm" style="max-height:200px">
                        ${btnSupprimer}
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            preview.html(`
                <div class="position-relative p-3 border rounded bg-light d-inline-block">
                    <p class="text-success mb-0"><i class="bi bi-camera-video me-1"></i>${escapeHtml(file.name)}</p>
                    ${btnSupprimer}
                </div>
            `);
        } else {
            preview.html(`
                <div class="position-relative p-3 border rounded bg-light d-inline-block">
                    <p class="text-info mb-0"><i class="bi bi-file-earmark me-1"></i>${escapeHtml(file.name)}</p>
                    ${btnSupprimer}
                </div>
            `);
        }
    });

    // Écouteur pour supprimer le fichier sélectionné
    $(document).on('click', '#btn-supprimer-media', function() {
        $('#input-fichier').val(''); // Réinitialiser l'input file
        $('#preview-fichier').empty(); // Vider l'aperçu
    });

    // Drag & drop zone
    const $dropzone = $('#dropzone');
    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        if (e.type === 'drop') {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('input-fichier').files = files;
                $('#input-fichier').trigger('change');
            }
        }
    });

});

// ===================== RÉAGIR À UN POST (AJAX) =====================

function reagir(postId, typeReaction, btn) {
    $.post('api/posts.php', {
        action:   'reagir',
        post_id:  postId,
        type:     typeReaction,
    }, function (res) {
        if (res.succes) {
            const $btn = $(btn);
            const $icon = $btn.find('i');

            if (res.etat === 'ajoute') {
                $btn.addClass('active text-primary');
                $icon.removeClass('bi-hand-thumbs-up').addClass('bi-hand-thumbs-up-fill');
            } else if (res.etat === 'retire') {
                $btn.removeClass('active text-primary');
                $icon.removeClass('bi-hand-thumbs-up-fill').addClass('bi-hand-thumbs-up');
            }

            // Mettre à jour le compteur dans le post
            const $compteur = $btn.closest('.post-card').find('.reaction-emoji').parent();
            if ($compteur.length && res.nb_reactions > 0) {
                $compteur.html(`<span class="reaction-emoji">👍</span> ${res.nb_reactions} réaction${res.nb_reactions > 1 ? 's' : ''}`);
            }
        }
    });
}

// ===================== AFFICHER / MASQUER COMMENTAIRES =====================

function afficherCommentaires(postId) {
    const $section = $(`#commentaires-${postId}`);

    if ($section.is(':visible')) {
        $section.slideUp(200);
        return;
    }

    // Charger les commentaires via AJAX
    $.get('api/posts.php', { action: 'charger_commentaires', post_id: postId }, function (res) {
        if (res.succes) {
            const $liste = $(`#liste-commentaires-${postId}`);
            $liste.empty();

            if (res.commentaires.length === 0) {
                $liste.html('<p class="text-muted small text-center">Aucun commentaire. Soyez le premier !</p>');
            } else {
                res.commentaires.forEach(function (c) {
                    $liste.append(creerHTMLCommentaire(c));
                });
                
                // Vérifier périodiquement si les boutons modifier doivent être masqués (délai de 30 secondes)
                verifierDelaiModificationCommentaires($liste);
            }

            $section.slideDown(200);
        }
    });
}

// ===================== VÉRIFIER LE DÉLAI DE MODIFICATION DES COMMENTAIRES =====================

/**
 * Vérifie périodiquement si les boutons "Modifier" doivent être masqués
 * (après 30 secondes depuis la publication du commentaire)
 */
function verifierDelaiModificationCommentaires($liste) {
    const intervalId = setInterval(function() {
        $liste.find('.btn-modifier-commentaire').each(function() {
            const $btn = $(this);
            const dateCommentaireStr = $btn.attr('data-date-commentaire');
            
            if (!dateCommentaireStr) {
                return; // Pas de date, on passe au suivant
            }
            
            // Calculer le temps écoulé
            const dateCommentaire = new Date(dateCommentaireStr);
            const maintenant = new Date();
            const secondesEcoulees = Math.floor((maintenant - dateCommentaire) / 1000);
            
            // Si le délai de 30 secondes est dépassé, masquer le bouton
            if (secondesEcoulees > 30) {
                $btn.fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
    }, 2000); // Vérifier toutes les 2 secondes
    
    // Nettoyer l'interval après 2 minutes (plus besoin de vérifier)
    setTimeout(function() {
        clearInterval(intervalId);
    }, 120000);
}

function creerHTMLCommentaire(c) {
    // Calculer si le commentaire peut encore être modifié (30 secondes)
    const dateCommentaire = new Date(c.date_commentaire);
    const maintenant = new Date();
    const secondesEcoulees = Math.floor((maintenant - dateCommentaire) / 1000);
    const peutModifier = c.peut_modifier || (secondesEcoulees <= 30 && c.utilisateur_id === (window.currentUserId || null));
    
    // Bouton modifier (seulement si l'utilisateur peut modifier)
    // On stocke la date dans un attribut data pour pouvoir vérifier le délai plus tard
    const boutonModifier = peutModifier ? `
        <button class="btn btn-sm btn-link p-0 text-decoration-none btn-modifier-commentaire" 
                onclick="editerCommentaire(${c.id}, '${escapeHtml(c.contenu.replace(/'/g, "\\'"))}')"
                data-commentaire-id="${c.id}"
                data-date-commentaire="${c.date_commentaire}"
                style="font-size:.7rem;color:var(--text-3);padding:0;margin-left:4px"
                title="Modifier (délai: 30 secondes)">
            <i class="bi bi-pencil"></i>
        </button>` : '';
    
    return `
    <div class="commentaire-item d-flex gap-2 mb-2" id="commentaire-${c.id}" data-date-commentaire="${c.date_commentaire}">
        <img src="${escapeHtml(c.avatar)}" alt="" class="rounded-circle flex-shrink-0"
             width="32" height="32" style="object-fit:cover">
        <div class="commentaire-bulle flex-grow-1">
            <div class="d-flex align-items-center gap-1">
                <div class="auteur">${escapeHtml(c.prenom + ' ' + c.nom)}</div>
                ${boutonModifier}
            </div>
            <div class="contenu" id="contenu-commentaire-${c.id}">${escapeHtml(c.contenu)}</div>
            <div class="temps">${tempsEcoule(c.date_commentaire)}</div>
        </div>
    </div>`;
}

// ===================== ENVOYER UN COMMENTAIRE =====================

function envoyerCommentaire(postId) {
    const $input   = $(`#input-commentaire-${postId}`);
    const contenu  = $input.val().trim();
    if (!contenu) return;

    $.post('api/posts.php', {
        action:  'commenter',
        post_id: postId,
        contenu: contenu,
    }, function (res) {
        if (res.succes) {
            $input.val('');
            const $liste = $(`#liste-commentaires-${postId}`);
            $liste.find('.text-muted').remove(); // retirer "aucun commentaire"
            // Ajouter l'ID utilisateur pour vérifier les permissions
            res.commentaire.utilisateur_id = window.currentUserId || null;
            res.commentaire.peut_modifier = true; // Nouveau commentaire, peut être modifié
            $liste.append(creerHTMLCommentaire(res.commentaire));
            
            // Vérifier périodiquement si le bouton modifier doit être masqué (délai de 30 secondes)
            verifierDelaiModificationCommentaires($liste);

            // Mettre à jour le compteur
            const $post = $(`#post-${postId}`);
            let $compteur = $post.find('[onclick*="afficherCommentaires"]').first();
            if ($compteur.length) {
                const actuel = parseInt($compteur.text()) || 0;
                const newCount = actuel + 1;
                $compteur.text(`${newCount} commentaire${newCount > 1 ? 's' : ''}`);
            }
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== MODIFIER UN COMMENTAIRE =====================

function editerCommentaire(commentaireId, contenuActuel) {
    const nouveauContenu = prompt('Modifier le commentaire :', contenuActuel);
    
    if (nouveauContenu === null || nouveauContenu.trim() === contenuActuel.trim()) {
        return; // Annulé ou pas de changement
    }
    
    if (!nouveauContenu.trim()) {
        afficherToast('Le commentaire ne peut pas être vide.', 'warning');
        return;
    }
    
    $.post('api/posts.php', {
        action: 'modifier_commentaire',
        commentaire_id: commentaireId,
        contenu: nouveauContenu.trim()
    }, function(res) {
        if (res.succes) {
            // Mettre à jour le contenu du commentaire dans l'interface
            $(`#contenu-commentaire-${commentaireId}`).text(res.commentaire.contenu);
            afficherToast('Commentaire modifié avec succès.', 'success');
            
            // Vérifier si le bouton modifier doit être retiré (délai dépassé)
            const dateCommentaire = new Date(res.commentaire.date_commentaire);
            const maintenant = new Date();
            const secondesEcoulees = Math.floor((maintenant - dateCommentaire) / 1000);
            if (secondesEcoulees > 30) {
                $(`#commentaire-${commentaireId} .btn-link`).remove();
            }
        } else {
            afficherToast(res.message || 'Erreur lors de la modification.', 'danger');
        }
    }).fail(function() {
        afficherToast('Erreur lors de la modification du commentaire.', 'danger');
    });
}

// ===================== PARTAGER UN POST =====================

function partager(postId) {
    const contenu = prompt('Ajouter un commentaire à ce partage (optionnel) :') ?? '';
    $.post('api/posts.php', {
        action:  'partager',
        post_id: postId,
        contenu: contenu,
    }, function (res) {
        if (res.succes) {
            afficherToast('Publication partagée !', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== MODIFIER UN POST =====================

function modifierPost(postId) {
    // Récupérer les données du post actuel
    const $postCard = $(`#post-${postId}`);
    const contenu = $postCard.find('.post-contenu').text().trim();
    const visibilite = $postCard.data('visibilite') || 'public';
    const lieu = $postCard.data('lieu') || '';
    const humeur = $postCard.data('humeur') || '';
    const fichier = $postCard.data('fichier') || '';

    // Créer un modal de modification
    const modalHtml = `
        <div class="modal fade" id="modalModifierPost" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Modifier la publication</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="formModifierPost" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="modifier">
                            <input type="hidden" name="post_id" value="${postId}">
                            
                            <textarea name="contenu" id="input-modifier-contenu" class="form-control border-0" rows="4"
                                      placeholder="Que souhaitez-vous partager ?" style="resize:none;font-size:1rem;background:var(--bg-3)">${escapeHtml(contenu)}</textarea>
                            
                            <div class="mt-3">
                                <label class="form-label small fw-semibold">Visibilité</label>
                                <select name="visibilite" class="form-select form-select-sm">
                                    <option value="public" ${visibilite === 'public' ? 'selected' : ''}>Public</option>
                                    <option value="amis" ${visibilite === 'amis' ? 'selected' : ''}>Amis</option>
                                    <option value="prive" ${visibilite === 'prive' ? 'selected' : ''}>Privé</option>
                                </select>
                            </div>
                            
                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <input type="text" name="lieu" class="form-control form-control-sm" 
                                           placeholder="Lieu" value="${escapeHtml(lieu)}">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="humeur" class="form-control form-control-sm" 
                                           placeholder="Humeur" value="${escapeHtml(humeur)}">
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label class="form-label small fw-semibold">Changer le fichier (optionnel)</label>
                                <input type="file" name="fichier" id="input-modifier-fichier" class="form-control form-control-sm">
                                ${fichier ? `<small class="text-muted d-block mt-1">Fichier actuel : ${escapeHtml(fichier.split('/').pop())}</small>` : ''}
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-ecein-primary">
                                <i class="bi bi-check2 me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    // Supprimer l'ancien modal s'il existe
    $('#modalModifierPost').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('modalModifierPost'));
    modal.show();

    // Gérer la soumission du formulaire
    $('#formModifierPost').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('[type="submit"]');
        const formData = new FormData(this);

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Modification...');

        $.ajax({
            url: 'api/posts.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.succes) {
                    modal.hide();
                    afficherToast(res.message, 'success');
                    // Recharger la page pour afficher les modifications
                    setTimeout(() => location.reload(), 500);
                } else {
                    afficherToast(res.message, 'danger');
                }
            },
            error: function() {
                afficherToast('Erreur lors de la modification.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-check2 me-2"></i>Enregistrer les modifications');
            }
        });
    });

    // Nettoyer le modal après fermeture
    $('#modalModifierPost').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// ===================== SUPPRIMER UN POST =====================

function supprimerPost(postId) {
    if (!confirm('Supprimer cette publication définitivement ?')) return;

    $.post('api/posts.php', {
        action:  'supprimer',
        post_id: postId,
    }, function (res) {
        if (res.succes) {
            $(`#post-${postId}`).fadeOut(400, function () { $(this).remove(); });
            afficherToast('Publication supprimée.', 'success');
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== ENVOYER DEMANDE D'AMI =====================

function envoyerDemande(userId, btn) {
    const $btn = $(btn);
    $btn.prop('disabled', true);

    $.post('api/amis.php', {
        action:           'envoyer_demande',
        destinataire_id:  userId,
    }, function (res) {
        if (res.succes) {
            $btn.html('<i class="bi bi-check2 me-1"></i>Invitation envoyée').removeClass('btn-outline-primary').addClass('btn-success');
            afficherToast(res.message, 'success');
        } else {
            $btn.prop('disabled', false);
            afficherToast(res.message, 'warning');
        }
    });
}

// ===================== RÉPONDRE À UNE INVITATION =====================

function repondreInvitation(connexionId, reponse, btn) {
    const $btn = $(btn);
    $btn.prop('disabled', true);

    $.post('api/amis.php', {
        action:       'repondre',
        connexion_id: connexionId,
        reponse:      reponse,
    }, function (res) {
        if (res.succes) {
            $(`#demande-${connexionId}`).fadeOut(400, function () { $(this).remove(); });
            afficherToast(res.message, 'success');
        } else {
            $btn.prop('disabled', false);
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== ANNULER UNE INVITATION =====================

function annulerInvitation(connexionId, btn) {
    if (!confirm('Annuler cette invitation ?')) return;
    const $btn = $(btn);

    $.post('api/amis.php', {
        action:       'annuler',
        connexion_id: connexionId,
    }, function (res) {
        if (res.succes) {
            $btn.closest('.col-sm-6').fadeOut(300, function () { $(this).remove(); });
            afficherToast('Invitation annulée.', 'info');
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== NE PLUS SUIVRE (SUPPRIMER CONNEXION) =====================

function supprimerConnexion(autreId, btn) {
    if (!confirm('Êtes-vous sûr de vouloir ne plus suivre cette personne ?')) return;
    const $btn = $(btn);
    $btn.prop('disabled', true);

    $.post('api/amis.php', {
        action:   'supprimer',
        autre_id: autreId,
    }, function (res) {
        if (res.succes) {
            afficherToast(res.message, 'success');
            // Recharger la page après un court délai pour actualiser l'interface
            setTimeout(() => location.reload(), 1000);
        } else {
            $btn.prop('disabled', false);
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== SUPPRIMER UNE FORMATION =====================

function supprimerFormation(id, btn) {
    if (!confirm('Supprimer cette formation ?')) return;
    $.post('api/profil.php', { action: 'supprimer_formation', id: id }, function (res) {
        if (res.succes) {
            $(btn).closest('.timeline-item').fadeOut(300);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== SUPPRIMER UN PROJET =====================

function supprimerProjet(id, btn) {
    if (!confirm('Supprimer ce projet ?')) return;
    $.post('api/profil.php', { action: 'supprimer_projet', id: id }, function (res) {
        if (res.succes) {
            $(btn).closest('.col-md-6').fadeOut(300);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== OUVRIR MEDIA EN PLEIN ÉCRAN =====================

function ouvrirMedia(src) {
    const modal = `
    <div class="modal fade" id="modalMedia" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-black">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img src="${escapeHtml(src)}" class="img-fluid" style="max-height:80vh">
                </div>
            </div>
        </div>
    </div>`;

    $('#modalMedia').remove();
    $('body').append(modal);
    const m = new bootstrap.Modal(document.getElementById('modalMedia'));
    m.show();
    document.getElementById('modalMedia').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

// ===================== POLLING NOTIFICATIONS =====================

// Vérifier les nouvelles notifications toutes les 30 secondes
setInterval(function () {
    $.get('api/notifications.php', { action: 'compter' }, function (res) {
        if (res.succes && res.count > 0) {
            const $badge = $('.nav-icon-link[href="notifications.php"] .badge-notif');
            if ($badge.length) {
                $badge.text(res.count);
            } else {
                $('.nav-icon-link[href="notifications.php"]').append(
                    `<span class="badge-notif">${res.count}</span>`
                );
            }
        }
    });
}, 30000);

// ===================== UTILITAIRES =====================

/**
 * Affiche un toast Bootstrap
 * @param {string} message
 * @param {string} type (success, danger, warning, info)
 */
function afficherToast(message, type = 'info') {
    const id     = 'toast-' + Date.now();
    const icons  = { success: 'check-circle-fill', danger: 'exclamation-triangle-fill', warning: 'exclamation-circle-fill', info: 'info-circle-fill' };
    const icon   = icons[type] || 'info-circle-fill';

    const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${icon} me-2"></i>${escapeHtml(message)}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    let $container = $('#toast-container');
    if (!$container.length) {
        $container = $('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>');
        $('body').append($container);
    }

    $container.append(html);

    // Auto-supprimer après 4 secondes
    setTimeout(() => $(`#${id}`).fadeOut(300, function () { $(this).remove(); }), 4000);
}

// ===================== AFFICHER DÉTAILS EMPLOI =====================

/**
 * Affiche un modal avec tous les détails d'une offre d'emploi
 * @param {Object} emploi - Objet contenant les données de l'offre d'emploi
 */
function afficherDetailsEmploi(emploi) {
    // Formater les dates
    const dateDebut = emploi.date_debut ? new Date(emploi.date_debut) : null;
    const dateLimite = emploi.date_limite ? new Date(emploi.date_limite) : null;
    const datePublication = emploi.date_publication ? new Date(emploi.date_publication) : null;
    
    const optionsDate = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    };
    
    const dateDebutStr = dateDebut ? dateDebut.toLocaleDateString('fr-FR', optionsDate) : null;
    const dateLimiteStr = dateLimite ? dateLimite.toLocaleDateString('fr-FR', optionsDate) : null;
    const datePublicationStr = datePublication ? datePublication.toLocaleDateString('fr-FR', optionsDate) : null;
    
    // Couleurs des badges selon le type
    const badgeColors = {
        'stage': 'bg-info',
        'alternance': 'bg-warning',
        'cdi': 'bg-success',
        'cdd': 'bg-secondary',
        'freelance': 'bg-purple',
        'benevole': 'bg-secondary'
    };
    const badgeClass = badgeColors[emploi.type] || 'bg-secondary';
    
    // Construire le HTML du modal
    let html = `
        <div class="modal fade" id="modalDetailsEmploi" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">${escapeHtml(emploi.titre)}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex gap-3 mb-3">
                            <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:var(--accent-light)">
                                <i class="bi bi-building" style="color:var(--accent);font-size:1.5rem"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-2" style="color:var(--accent);font-size:1rem">${escapeHtml(emploi.entreprise)}</div>
                                <span class="badge ${badgeClass}" style="font-size:.7rem">${escapeHtml(emploi.type ? emploi.type.charAt(0).toUpperCase() + emploi.type.slice(1) : '')}</span>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            ${emploi.lieu ? `
                            <div class="col-md-6">
                                <strong><i class="bi bi-geo-alt me-2"></i>Lieu :</strong><br>
                                <span style="color:var(--text-2)">${escapeHtml(emploi.lieu)}</span>
                            </div>
                            ` : ''}
                            ${emploi.salaire ? `
                            <div class="col-md-6">
                                <strong><i class="bi bi-cash me-2"></i>Salaire :</strong><br>
                                <span style="color:var(--text-2)">${escapeHtml(emploi.salaire)}</span>
                            </div>
                            ` : ''}
                            ${dateDebutStr ? `
                            <div class="col-md-6">
                                <strong><i class="bi bi-calendar-event me-2"></i>Date de début :</strong><br>
                                <span style="color:var(--text-2)">${dateDebutStr}</span>
                            </div>
                            ` : ''}
                            ${dateLimiteStr ? `
                            <div class="col-md-6">
                                <strong><i class="bi bi-clock me-2" style="color:var(--rose)"></i>Date limite :</strong><br>
                                <span style="color:var(--rose)">${dateLimiteStr}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${emploi.description ? `
                        <div class="mb-3">
                            <strong><i class="bi bi-file-text me-2"></i>Description :</strong>
                            <div class="mt-2" style="color:var(--text-2);white-space:pre-wrap">${escapeHtml(emploi.description)}</div>
                        </div>
                        ` : ''}
                        
                        ${emploi.lien_candidature ? `
                        <div class="mb-2">
                            <strong><i class="bi bi-link-45deg me-2"></i>Lien de candidature :</strong><br>
                            <a href="${escapeHtml(emploi.lien_candidature)}" target="_blank" rel="noopener" style="color:var(--accent)">${escapeHtml(emploi.lien_candidature)}</a>
                        </div>
                        ` : ''}
                        
                        ${emploi.contact_email ? `
                        <div class="mb-2">
                            <strong><i class="bi bi-envelope me-2"></i>Email de contact :</strong><br>
                            <a href="mailto:${escapeHtml(emploi.contact_email)}" style="color:var(--accent)">${escapeHtml(emploi.contact_email)}</a>
                        </div>
                        ` : ''}
                        
                        ${emploi.prenom && emploi.nom ? `
                        <div class="mb-2">
                            <strong><i class="bi bi-person me-2"></i>Publié par :</strong><br>
                            <span style="color:var(--text-2)">${escapeHtml(emploi.prenom + ' ' + emploi.nom)}</span>
                            ${datePublicationStr ? ` · ${datePublicationStr}` : ''}
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                        <button class="btn btn-light btn-favori-modal" 
                                data-emploi-id="${emploi.id}" 
                                onclick="toggleFavori(${emploi.id}, this)" 
                                title="Sauvegarder">
                            <i class="bi bi-bookmark"></i>
                        </button>
                        <a href="candidater.php?id=${emploi.id}" class="btn btn-ecein-primary"><i class="bi bi-send me-1"></i>Candidater</a>
                        ${emploi.contact_email ? `<a href="contacter.php?id=${emploi.id}" class="btn btn-outline-primary"><i class="bi bi-envelope me-1"></i>Contacter</a>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Supprimer l'ancien modal s'il existe
    const ancienModal = document.getElementById('modalDetailsEmploi');
    if (ancienModal) {
        ancienModal.remove();
    }
    
    // Ajouter le nouveau modal au body
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetailsEmploi'));
    modal.show();
    
    // Vérifier l'état du favori pour le bouton du modal
    const btnFavoriModal = document.querySelector('#modalDetailsEmploi .btn-favori-modal');
    if (btnFavoriModal) {
        verifierFavori(emploi.id, btnFavoriModal);
    }
    
    // Nettoyer le modal quand il est fermé
    document.getElementById('modalDetailsEmploi').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// ===================== AFFICHER DÉTAILS ÉVÉNEMENT =====================

/**
 * Affiche un modal avec tous les détails d'un événement
 * @param {Object} evt - Objet contenant les données de l'événement
 */
function afficherDetailsEvenement(evt) {
    // Formater les dates
    const dateDebut = new Date(evt.date_debut);
    const dateFin = evt.date_fin ? new Date(evt.date_fin) : null;
    
    const optionsDate = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    const dateDebutStr = dateDebut.toLocaleDateString('fr-FR', optionsDate);
    const dateFinStr = dateFin ? dateFin.toLocaleDateString('fr-FR', optionsDate) : null;
    
    // Construire le HTML du modal
    let html = `
        <div class="modal fade" id="modalDetailsEvenement" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">${escapeHtml(evt.titre)}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex gap-3 mb-3">
                            <div class="evenement-date-badge text-center flex-shrink-0">
                                <div class="evt-mois">${dateDebut.toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase()}</div>
                                <div class="evt-jour">${dateDebut.getDate()}</div>
                            </div>
                            <div class="flex-grow-1">
                                ${evt.est_officiel ? '<span class="badge bg-ecein mb-2">Officiel ECE</span>' : ''}
                                <div class="mb-2">
                                    <strong><i class="bi bi-calendar-event me-2"></i>Date de début :</strong><br>
                                    <span style="color:var(--text-2)">${dateDebutStr}</span>
                                </div>
                                ${dateFinStr ? `
                                <div class="mb-2">
                                    <strong><i class="bi bi-calendar-check me-2"></i>Date de fin :</strong><br>
                                    <span style="color:var(--text-2)">${dateFinStr}</span>
                                </div>
                                ` : ''}
                                ${evt.lieu ? `
                                <div class="mb-2">
                                    <strong><i class="bi bi-geo-alt me-2"></i>Lieu :</strong><br>
                                    <span style="color:var(--text-2)">${escapeHtml(evt.lieu)}</span>
                                </div>
                                ` : ''}
                                ${evt.prenom && evt.nom ? `
                                <div class="mb-2">
                                    <strong><i class="bi bi-person me-2"></i>Organisateur :</strong><br>
                                    <span style="color:var(--text-2)">${escapeHtml(evt.prenom + ' ' + evt.nom)}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        ${evt.description ? `
                        <div class="mb-3">
                            <strong><i class="bi bi-file-text me-2"></i>Description :</strong>
                            <div class="mt-2" style="color:var(--text-2);white-space:pre-wrap">${escapeHtml(evt.description)}</div>
                        </div>
                        ` : ''}
                        ${evt.lien ? `
                        <div class="mb-3">
                            <strong><i class="bi bi-link-45deg me-2"></i>Lien :</strong><br>
                            <a href="${escapeHtml(evt.lien)}" target="_blank" rel="noopener" style="color:var(--accent)">${escapeHtml(evt.lien)}</a>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                        ${evt.lien ? `<a href="${escapeHtml(evt.lien)}" target="_blank" rel="noopener" class="btn btn-ecein-primary">Voir le lien</a>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Supprimer l'ancien modal s'il existe
    const ancienModal = document.getElementById('modalDetailsEvenement');
    if (ancienModal) {
        ancienModal.remove();
    }
    
    // Ajouter le nouveau modal au body
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetailsEvenement'));
    modal.show();
    
    // Nettoyer le modal quand il est fermé
    document.getElementById('modalDetailsEvenement').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Échappe les caractères HTML dangereux
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Calcule le temps écoulé (version JS)
 * @param {string} dateStr
 * @returns {string}
 */
function tempsEcoule(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    // Afficher l'heure exacte (HH:MM) pour les messages récents (moins de 24h)
    if (diff < 86400) {
        const date = new Date(dateStr);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return hours + ':' + minutes;
    }
    if (diff < 604800) return Math.floor(diff / 86400) + ' j';
    return new Date(dateStr).toLocaleDateString('fr-FR');
}

// ===================== GESTION DU THÈME COULEUR =====================
window.changerTheme = function(themeName) {
    const themes = ['theme-rouge', 'theme-jaune', 'theme-violet'];
    
    // Retirer les thèmes actuels
    themes.forEach(t => document.body.classList.remove(t));
    
    // Ajouter le nouveau s'il n'est pas par défaut (bleu)
    if (themeName !== 'bleu') {
        document.body.classList.add('theme-' + themeName);
    }
    
    // Sauvegarder le choix pour l'utilisateur actuel
    const uid = window.currentUserIdTheme || 'guest';
    localStorage.setItem('ecein_theme_' + uid, themeName);
    
    // Optionnel : afficher une petite notif
    let nomTheme = 'Bleu (Défaut)';
    if (themeName === 'rouge') nomTheme = 'Rouge';
    if (themeName === 'jaune') nomTheme = 'Jaune orangé';
    if (themeName === 'violet') nomTheme = 'Violet';
    afficherToast('Thème changé en ' + nomTheme, 'success');
};

// ===================== GESTION DES FAVORIS EMPLOIS =====================

/**
 * Vérifie si une offre est en favoris et met à jour le bouton
 * @param {number} emploiId - ID de l'offre d'emploi
 * @param {HTMLElement} bouton - Bouton bookmark à mettre à jour
 */
function verifierFavori(emploiId, bouton) {
    if (!bouton) return;
    
    fetch(`api/favoris_emplois.php?action=verifier&emploi_id=${emploiId}`)
        .then(response => response.json())
        .then(data => {
            if (data.succes && data.est_favori) {
                bouton.classList.add('active');
                const icon = bouton.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-bookmark');
                    icon.classList.add('bi-bookmark-fill');
                }
            }
        })
        .catch(error => console.error('Erreur vérification favori:', error));
}

/**
 * Ajoute ou retire une offre des favoris
 * @param {number} emploiId - ID de l'offre d'emploi
 * @param {HTMLElement} bouton - Bouton bookmark cliqué
 */
function toggleFavori(emploiId, bouton) {
    if (event) {
        event.stopPropagation();
    }
    
    if (!bouton) return;
    
    const estActif = bouton.classList.contains('active');
    const action = estActif ? 'retirer' : 'ajouter';
    
    fetch('api/favoris_emplois.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&emploi_id=${emploiId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.succes) {
            const icon = bouton.querySelector('i');
            if (action === 'ajouter') {
                bouton.classList.add('active');
                if (icon) {
                    icon.classList.remove('bi-bookmark');
                    icon.classList.add('bi-bookmark-fill');
                }
                afficherToast('Offre ajoutée aux favoris', 'success');
            } else {
                bouton.classList.remove('active');
                if (icon) {
                    icon.classList.remove('bi-bookmark-fill');
                    icon.classList.add('bi-bookmark');
                }
                afficherToast('Offre retirée des favoris', 'info');
            }
        } else {
            afficherToast(data.message || 'Erreur', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        afficherToast('Erreur lors de la sauvegarde', 'error');
    });
}
