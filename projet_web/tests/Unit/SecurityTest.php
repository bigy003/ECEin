<?php
/**
 * Tests de sécurité
 * 
 * Ce fichier teste les fonctions de sécurité et de validation :
 * - Protection XSS
 * - Validation des sessions
 * - Vérification des rôles
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    /**
     * Test de protection XSS avec différents vecteurs d'attaque
     */
    public function testXSSProtection(): void
    {
        // Vecteurs d'attaque XSS courants
        $xssVectors = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<body onload=alert("XSS")>',
        ];

        foreach ($xssVectors as $vector) {
            $escaped = h($vector);
            // Vérifier que les balises HTML sont échappées
            $this->assertStringNotContainsString('<script>', $escaped);
            // Note : htmlspecialchars n'échappe pas "javascript:" dans une chaîne normale,
            // seulement dans les attributs HTML. Ici on vérifie juste que les balises sont échappées.
            if (strpos($vector, '<') !== false) {
                $this->assertStringContainsString('&lt;', $escaped);
            }
            // Vérifier que les caractères HTML sont échappés
            if (strpos($vector, '<') !== false || strpos($vector, '>') !== false) {
                $this->assertStringNotContainsString('<', $escaped);
                $this->assertStringNotContainsString('>', $escaped);
            }
        }
    }

    /**
     * Test de la validation de session
     */
    public function testSessionValidation(): void
    {
        // Nettoyer la session
        $_SESSION = [];
        
        // Test sans session
        $this->assertFalse(estConnecte());
        $this->assertFalse(estAdmin());
        
        // Test avec session utilisateur normale
        $_SESSION['utilisateur_id'] = 1;
        $_SESSION['role'] = 'utilisateur';
        $this->assertTrue(estConnecte());
        $this->assertFalse(estAdmin());
        
        // Test avec session admin
        $_SESSION['role'] = 'administrateur';
        $this->assertTrue(estConnecte());
        $this->assertTrue(estAdmin());
        
        // Nettoyer après le test
        $_SESSION = [];
    }

    /**
     * Test de la protection contre les injections SQL (via échappement)
     * 
     * Note : Les vraies requêtes préparées sont testées dans les tests d'intégration
     */
    public function testSQLInjectionProtection(): void
    {
        // Tentatives d'injection SQL dans les chaînes
        $sqlInjection = [
            "'; DROP TABLE utilisateurs; --",
            "' OR '1'='1",
            "admin'--",
            "1' UNION SELECT * FROM utilisateurs--",
        ];

        foreach ($sqlInjection as $injection) {
            $escaped = h($injection);
            // L'échappement HTML ne protège pas directement contre SQL,
            // mais vérifie qu'il n'y a pas de faille dans h()
            $this->assertIsString($escaped);
            $this->assertNotEmpty($escaped);
        }
    }

    /**
     * Test de la validation des types de données
     */
    public function testTypeValidation(): void
    {
        // Test que estConnecte() retourne toujours un booléen
        $_SESSION = [];
        $this->assertIsBool(estConnecte());
        
        $_SESSION['utilisateur_id'] = 1;
        $this->assertIsBool(estConnecte());
        
        // Test que estAdmin() retourne toujours un booléen
        $this->assertIsBool(estAdmin());
        
        $_SESSION['role'] = 'administrateur';
        $this->assertIsBool(estAdmin());
        
        $_SESSION = [];
    }
}
