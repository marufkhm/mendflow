-- Training Tool resource kind for project_resources

ALTER TABLE project_resources
    MODIFY kind ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other';
