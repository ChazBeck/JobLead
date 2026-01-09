-- Create jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company VARCHAR(255) NOT NULL,
    role_title VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    posted_date DATE,
    last_seen_date DATE,
    why_now LONGTEXT,
    verification_level VARCHAR(100),
    confidence VARCHAR(50),
    revenue_tier VARCHAR(50),
    revenue_estimate VARCHAR(255),
    revenue_confidence VARCHAR(50),
    fit_score INT,
    engagement_type VARCHAR(255),
    recommended_angle LONGTEXT,
    industry VARCHAR(255),
    source_link LONGTEXT,
    parent_company VARCHAR(255),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create contacts table for Likely Buyers/Managers
CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    name VARCHAR(255),
    title VARCHAR(255),
    confidence VARCHAR(50),
    source LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Create index for faster queries
CREATE INDEX idx_company ON jobs(company);
CREATE INDEX idx_status ON jobs(status);
CREATE INDEX idx_fit_score ON jobs(fit_score);
