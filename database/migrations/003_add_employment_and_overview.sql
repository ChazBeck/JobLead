-- Add new fields for alternative JSON structure
-- Employment Type and Job Overview

-- Add employment_type column
ALTER TABLE jobs ADD COLUMN employment_type VARCHAR(100) AFTER location;

-- Add job_overview column (separate from job_description)
ALTER TABLE jobs ADD COLUMN job_overview LONGTEXT AFTER job_description;
