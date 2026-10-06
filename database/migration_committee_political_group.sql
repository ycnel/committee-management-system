-- Add a political-group flag to each committee membership.
-- This is a non-breaking, nullable field used for Majority/Minority labeling.

ALTER TABLE committee_members
    ADD COLUMN political_group ENUM('Majority','Minority') NULL DEFAULT NULL
    AFTER member_role;
