-- ========================================
-- Production Migration: Main to Enhancements Branch
-- Run this in your production database (charle22_job_lead)
-- ========================================

-- Migration 1: Add ESG offering detection columns
ALTER TABLE jobs
ADD COLUMN sustainability_reporting TINYINT(1) DEFAULT NULL COMMENT 'Sustainability Reporting & Disclosure',
ADD COLUMN data_management_esg TINYINT(1) DEFAULT NULL COMMENT 'Data Management & ESG Metrics',
ADD COLUMN esg_strategy_roadmapping TINYINT(1) DEFAULT NULL COMMENT 'ESG Strategy & Roadmapping',
ADD COLUMN regulatory_compliance TINYINT(1) DEFAULT NULL COMMENT 'Regulatory Compliance & Standards',
ADD COLUMN esg_ratings_rankings TINYINT(1) DEFAULT NULL COMMENT 'ESG Ratings & Rankings',
ADD COLUMN stakeholder_engagement TINYINT(1) DEFAULT NULL COMMENT 'Stakeholder Engagement & Communication',
ADD COLUMN governance_policy TINYINT(1) DEFAULT NULL COMMENT 'Governance & Policy Development',
ADD COLUMN technology_tools TINYINT(1) DEFAULT NULL COMMENT 'Technology & Tools for Sustainability',
ADD COLUMN ai_analysis_notes TEXT DEFAULT NULL COMMENT 'OpenAI analysis confirmation notes',
ADD COLUMN ai_analyzed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When AI analysis completed';

-- Add index for querying by offerings
CREATE INDEX idx_offerings ON jobs (
    sustainability_reporting,
    data_management_esg,
    esg_strategy_roadmapping,
    regulatory_compliance,
    esg_ratings_rankings,
    stakeholder_engagement,
    governance_policy,
    technology_tools
);

-- Migration 2: Add job_description column
ALTER TABLE jobs 
ADD COLUMN job_description LONGTEXT NULL 
AFTER role_title;

-- ========================================
-- Verification Query (run after migration)
-- ========================================
-- This should show all the new columns
-- DESCRIBE jobs;
