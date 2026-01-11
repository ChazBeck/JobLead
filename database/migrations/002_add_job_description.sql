-- Add job_description column to jobs table
-- Run this migration to support the new "Job Description" field in JSON uploads

ALTER TABLE jobs 
ADD COLUMN job_description LONGTEXT NULL 
AFTER role_title;
