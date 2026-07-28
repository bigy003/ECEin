<?php
/**
 * Tests unitaires pour les fonctions utilitaires
 * 
 * Ce fichier teste les fonctions définies dans config/config.php :
 * - h() : Échappement HTML
 * - estConnecte() : Vérification de connexion
 * - estAdmin() : Vérification du rôle admin
 * - formatDateFr() : Formatage de dates en français
 * - tempsEcoule() : Calcul du temps écoulé
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    /**
     * Test de la fonction h() - Échappement HTML
     * 
     * Vérifie que les caractères spéciaux HTML sont correctement échappés
     * pour prévenir les attaques XSS
     */
    public function testHEscapeHtml(): void
    {
        // Test avec des balises HTML
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', 
            h('<script>alert("XSS")</script>'));
        
        // Test avec des guillemets simples et doubles
        // Note : htmlspecialchars avec ENT_QUOTES utilise &#039; pour les guillemets simples
        $this->assertEquals('&quot;Hello&quot; and &#039;World&#039;', 
            h('"Hello" and \'World\''));
        
        // Test avec des caractères spéciaux
        $this->assertEquals('&lt;div&gt;Test&lt;/div&gt;', 
            h('<div>Test</div>'));
        
        // Test avec une chaîne normale (pas de changement)
        $this->assertEquals('Hello World', 
            h('Hello World'));
        
        // Test avec une chaîne vide
        $this->assertEquals('', h(''));
    }

    /**
     * Test de la fonction estConnecte() - Vérification de connexion
     * 
     * Vérifie que la fonction détecte correctement si un utilisateur est connecté
     */
    public function testEstConnecte(): void
    {
        // Nettoyer la session avant le test
        $_SESSION = [];
        
        // Test sans connexion
        $this->assertFalse(estConnecte());
        
        // Simuler une connexion
        $_SESSION['utilisateur_id'] = 1;
        $this->assertTrue(estConnecte());
        
        // Nettoyer après le test
        $_SESSION = [];
    }

    /**
     * Test de la fonction estAdmin() - Vérification du rôle administrateur
     * 
     * Vérifie que la fonction détecte correctement si un utilisateur est admin
     */
    public function testEstAdmin(): void
    {
        // Nettoyer la session avant le test
        $_SESSION = [];
        
        // Test sans connexion
        $this->assertFalse(estAdmin());
        
        // Test avec connexion mais pas admin
        $_SESSION['utilisateur_id'] = 1;
        $_SESSION['role'] = 'utilisateur';
        $this->assertFalse(estAdmin());
        
        // Test avec connexion et rôle admin
        $_SESSION['role'] = 'administrateur';
        $this->assertTrue(estAdmin());
        
        // Nettoyer après le test
        $_SESSION = [];
    }

    /**
     * Test de la fonction formatDateFr() - Formatage de dates en français
     * 
     * Vérifie que les dates sont correctement formatées en français
     */
    public function testFormatDateFr(): void
    {
        // Test avec une date spécifique
        $date = '2024-03-15 14:30:00';
        $result = formatDateFr($date);
        
        // Vérifier que le format contient le jour, le mois en français et l'année
        $this->assertStringContainsString('15', $result);
        $this->assertStringContainsString('mars', $result);
        $this->assertStringContainsString('2024', $result);
        
        // Test avec une autre date
        $date2 = '2024-12-25 00:00:00';
        $result2 = formatDateFr($date2);
        $this->assertStringContainsString('25', $result2);
        $this->assertStringContainsString('décembre', $result2);
        
        // Test avec janvier
        $date3 = '2024-01-01 00:00:00';
        $result3 = formatDateFr($date3);
        $this->assertStringContainsString('janvier', $result3);
    }

    /**
     * Test de la fonction tempsEcoule() - Calcul du temps écoulé
     * 
     * Vérifie que le temps écoulé est correctement calculé et formaté
     */
    public function testTempsEcoule(): void
    {
        // Test avec une date récente (moins de 24h) - doit retourner l'heure
        $dateRecent = date('Y-m-d H:i:s', time() - 3600); // Il y a 1 heure
        $result = tempsEcoule($dateRecent);
        
        // Doit être au format HH:MM
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result);
        
        // Test avec une date de quelques jours (moins d'une semaine)
        $dateJours = date('Y-m-d H:i:s', time() - (3 * 86400)); // Il y a 3 jours
        $result2 = tempsEcoule($dateJours);
        $this->assertStringContainsString(' j', $result2);
        $this->assertStringContainsString('3', $result2);
        
        // Test avec une date ancienne (plus d'une semaine) - doit retourner formatDateFr
        $dateAncienne = '2023-01-01 00:00:00';
        $result3 = tempsEcoule($dateAncienne);
        // Doit contenir le mois en français
        $this->assertStringContainsString('janvier', $result3);
    }

    /**
     * Test de la fonction h() avec des caractères Unicode
     * 
     * Vérifie que les caractères Unicode sont correctement gérés
     */
    public function testHEscapeUnicode(): void
    {
        // Test avec des emojis
        $emoji = 'Hello 😀 World';
        $result = h($emoji);
        $this->assertEquals('Hello 😀 World', $result); // Les emojis ne sont pas échappés
        
        // Test avec des caractères accentués
        $accentue = 'Café résumé été';
        $result2 = h($accentue);
        $this->assertEquals('Café résumé été', $result2);
    }

    /**
     * Test de la fonction h() avec des valeurs null
     * 
     * Vérifie le comportement avec des valeurs null (doit convertir en chaîne)
     */
    public function testHWithNull(): void
    {
        // Note : h() attend un string, mais testons le comportement avec conversion
        $nullValue = null;
        // PHP convertira null en chaîne vide
        $this->assertEquals('', h((string)$nullValue));
    }
}
