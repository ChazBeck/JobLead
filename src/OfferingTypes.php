<?php
/**
 * ESG Offering Types
 * Centralized definition of all ESG offerings
 */

class OfferingTypes {
    
    /**
     * Get all offering definitions
     * @return array Associative array with offering key as index
     */
    public static function getAll() {
        return [
            'sustainability_reporting' => [
                'key' => 'sustainability_reporting',
                'label' => 'Sustainability Reporting',
                'full_label' => 'Sustainability Reporting & Disclosure'
            ],
            'data_management_esg' => [
                'key' => 'data_management_esg',
                'label' => 'Data Management',
                'full_label' => 'Data Management & ESG Metrics'
            ],
            'esg_strategy_roadmapping' => [
                'key' => 'esg_strategy_roadmapping',
                'label' => 'ESG Strategy',
                'full_label' => 'ESG Strategy & Roadmapping'
            ],
            'regulatory_compliance' => [
                'key' => 'regulatory_compliance',
                'label' => 'Compliance',
                'full_label' => 'Regulatory Compliance & Standards'
            ],
            'esg_ratings_rankings' => [
                'key' => 'esg_ratings_rankings',
                'label' => 'ESG Ratings',
                'full_label' => 'ESG Ratings & Rankings'
            ],
            'stakeholder_engagement' => [
                'key' => 'stakeholder_engagement',
                'label' => 'Stakeholder Engagement',
                'full_label' => 'Stakeholder Engagement & Communication'
            ],
            'governance_policy' => [
                'key' => 'governance_policy',
                'label' => 'Governance',
                'full_label' => 'Governance & Policy Development'
            ],
            'technology_tools' => [
                'key' => 'technology_tools',
                'label' => 'Technology',
                'full_label' => 'Technology & Tools for Sustainability'
            ]
        ];
    }
    
    /**
     * Get array of valid offering keys
     * @return array
     */
    public static function getValidKeys() {
        return array_keys(self::getAll());
    }
    
    /**
     * Get short labels for all offerings
     * @return array Key => short label
     */
    public static function getLabels() {
        return array_map(function($offering) {
            return $offering['label'];
        }, self::getAll());
    }
    
    /**
     * Get full labels for all offerings
     * @return array Key => full label
     */
    public static function getFullLabels() {
        return array_map(function($offering) {
            return $offering['full_label'];
        }, self::getAll());
    }
    
    /**
     * Check if an offering key is valid
     * @param string $key
     * @return bool
     */
    public static function isValid($key) {
        return in_array($key, self::getValidKeys());
    }
}
