<?php
/**
 * Tests pour les fonctions de formatage de dates
 * 
 * Tests détaillés pour formatDateFr() et tempsEcoule()
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DateFormatTest extends TestCase
{
    /**
     * Test complet de formatDateFr() avec tous les mois
     */
    public function testFormatDateFrAllMonths(): void
    {
        $months = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        foreach ($months as $monthNum => $monthName) {
            $date = sprintf('2024-%02d-15 12:00:00', $monthNum);
            $result = formatDateFr($date);
            
            $this->assertStringContainsString('15', $result);
            $this->assertStringContainsString($monthName, $result);
            $this->assertStringContainsString('2024', $result);
        }
    }

    /**
     * Test de formatDateFr() avec différents jours
     */
    public function testFormatDateFrDifferentDays(): void
    {
        $days = [1, 15, 28, 31];
        
        foreach ($days as $day) {
            $date = sprintf('2024-03-%02d 12:00:00', $day);
            $result = formatDateFr($date);
            
            $this->assertStringContainsString((string)$day, $result);
            $this->assertStringContainsString('mars', $result);
        }
    }

    /**
     * Test de tempsEcoule() avec différentes périodes
     */
    public function testTempsEcouleDifferentPeriods(): void
    {
        // Test : il y a 1 heure (doit retourner HH:MM)
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
        $result = tempsEcoule($oneHourAgo);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result);
        
        // Test : il y a 2 jours (doit retourner "2 j")
        $twoDaysAgo = date('Y-m-d H:i:s', time() - (2 * 86400));
        $result2 = tempsEcoule($twoDaysAgo);
        $this->assertStringContainsString(' j', $result2);
        $this->assertStringContainsString('2', $result2);
        
        // Test : il y a 6 jours (doit retourner "6 j")
        $sixDaysAgo = date('Y-m-d H:i:s', time() - (6 * 86400));
        $result3 = tempsEcoule($sixDaysAgo);
        $this->assertStringContainsString(' j', $result3);
        
        // Test : il y a 2 semaines (doit retourner formatDateFr)
        $twoWeeksAgo = date('Y-m-d H:i:s', time() - (14 * 86400));
        $result4 = tempsEcoule($twoWeeksAgo);
        // Doit contenir un mois en français
        $this->assertTrue(
            strpos($result4, 'janvier') !== false ||
            strpos($result4, 'février') !== false ||
            strpos($result4, 'mars') !== false ||
            strpos($result4, 'avril') !== false ||
            strpos($result4, 'mai') !== false ||
            strpos($result4, 'juin') !== false ||
            strpos($result4, 'juillet') !== false ||
            strpos($result4, 'août') !== false ||
            strpos($result4, 'septembre') !== false ||
            strpos($result4, 'octobre') !== false ||
            strpos($result4, 'novembre') !== false ||
            strpos($result4, 'décembre') !== false
        );
    }

    /**
     * Test de tempsEcoule() avec une date très récente (maintenant)
     */
    public function testTempsEcouleNow(): void
    {
        $now = date('Y-m-d H:i:s');
        $result = tempsEcoule($now);
        
        // Doit retourner l'heure actuelle au format HH:MM
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result);
    }

    /**
     * Test de tempsEcoule() avec une date future
     */
    public function testTempsEcouleFuture(): void
    {
        $future = date('Y-m-d H:i:s', time() + 3600); // Dans 1 heure
        $result = tempsEcoule($future);
        
        // Doit quand même retourner quelque chose (probablement l'heure)
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test de formatDateFr() avec différentes années
     */
    public function testFormatDateFrDifferentYears(): void
    {
        $years = [2020, 2024, 2025, 2030];
        
        foreach ($years as $year) {
            $date = sprintf('%d-06-15 12:00:00', $year);
            $result = formatDateFr($date);
            
            $this->assertStringContainsString((string)$year, $result);
            $this->assertStringContainsString('juin', $result);
        }
    }
}
