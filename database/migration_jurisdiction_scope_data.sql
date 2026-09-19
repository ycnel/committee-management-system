-- ===========================================================
-- database/migration_jurisdiction_scope_data.sql
-- Populates the existing jurisdiction records with general,
-- jurisdiction-specific scope descriptions.
-- Existing IDs and relationships are preserved.
-- ===========================================================

USE committee_management_db;

UPDATE jurisdictions
SET
    scope_definition = 'Matters concerning public health protection, sanitation services, disease prevention, health facilities, and local policies that support safe and healthy communities.',
    covered_areas = 'Public health programs\nSanitation and hygiene\nDisease prevention and health promotion\nPublic health facilities and services\nFood, water, and environmental health concerns',
    primary_responsibilities = 'Review local measures that protect community health\nExamine reports and recommendations on sanitation and health services\nConsider access, quality, and delivery of local health programs\nCoordinate legislative review of health-related concerns with relevant offices and stakeholders',
    typical_legislative_matters = 'Health and sanitation ordinances\nPublic health programs and service policies\nLocal measures for disease prevention\nRegulation of sanitation-related practices\nResolutions concerning health facilities and community health initiatives',
    outside_scope = 'Matters primarily concerning budgeting, infrastructure, public safety, or environmental management unless they have a direct health component\nClinical or professional decisions reserved for qualified health authorities\nMatters under the exclusive authority of national government agencies',
    notes = 'This is a general legislative scope description for local committee reference and is not an official statement of legal authority. Related matters may be coordinated with other jurisdictions when responsibilities overlap.'
WHERE jurisdiction_id = 1;

UPDATE jurisdictions
SET
    scope_definition = 'Matters concerning the preparation, review, authorization, and monitoring of public funds, local revenues, expenditures, and financial policies of the local government.',
    covered_areas = 'Annual and supplemental budgets\nPublic expenditures and appropriations\nLocal revenues, fees, and charges\nFinancial controls and fiscal reporting\nAllocation of funds to programs, services, and projects',
    primary_responsibilities = 'Review proposed budgets and requests for appropriations\nExamine whether proposed expenditures support approved programs and services\nReview financial reports and fiscal recommendations\nConsider local revenue and expenditure policies\nAssess the financial implications of proposed measures',
    typical_legislative_matters = 'Annual budget ordinances\nSupplemental budget measures\nAppropriation ordinances and resolutions\nLocal revenue and fee measures\nPolicies on expenditure controls, fund allocation, and fiscal reporting',
    outside_scope = 'Technical implementation of accounting procedures assigned to authorized finance offices\nMatters primarily concerning health, public safety, infrastructure, or environmental policy unless their funding is under review\nNational appropriations and fiscal matters outside local legislative authority',
    notes = 'This description presents a general local legislative scope and does not establish official fiscal authority or replace applicable budgeting, auditing, and procurement rules.'
WHERE jurisdiction_id = 2;

UPDATE jurisdictions
SET
    scope_definition = 'Matters concerning public safety, peace and order, emergency preparedness, community security, and local policies that help protect residents and maintain orderly communities.',
    covered_areas = 'Peace and order programs\nCommunity safety and crime prevention\nEmergency preparedness and response\nDisaster risk reduction coordination\nPublic safety facilities, services, and local enforcement support',
    primary_responsibilities = 'Review local policies supporting peace, order, and public safety\nExamine reports on safety conditions and emergency readiness\nConsider measures that improve community protection and incident response\nReview coordination arrangements among local safety offices and stakeholders\nMonitor legislative concerns affecting public order',
    typical_legislative_matters = 'Peace and order ordinances\nPublic safety and emergency preparedness measures\nDisaster risk reduction policies\nCommunity safety programs\nResolutions concerning local enforcement support and emergency response',
    outside_scope = 'Operational investigations or enforcement decisions assigned to law enforcement and emergency authorities\nMatters primarily concerning health, transport, budgeting, or infrastructure unless they directly affect public safety\nCriminal prosecution and national security matters outside local legislative authority',
    notes = 'This is a general local legislative scope description. It supports committee classification and review but does not confer enforcement powers or replace the mandates of competent authorities.'
WHERE jurisdiction_id = 3;

UPDATE jurisdictions
SET
    scope_definition = 'Matters concerning the planning, development, maintenance, and improvement of public infrastructure, public works, roads, facilities, and related local development policies.',
    covered_areas = 'Roads, streets, bridges, and drainage\nPublic buildings and facilities\nFlood control and related public works\nInfrastructure planning and project implementation\nConstruction, maintenance, and accessibility of local facilities',
    primary_responsibilities = 'Review infrastructure and public works proposals\nExamine project plans, implementation reports, and maintenance needs\nConsider whether public facilities respond to community needs\nReview policies for safe, accessible, and sustainable local infrastructure\nAssess the public works implications of proposed legislative measures',
    typical_legislative_matters = 'Infrastructure project ordinances and resolutions\nRoad, drainage, and public facility policies\nPublic works programs and funding requests\nMeasures concerning construction, maintenance, and accessibility\nLocal development policies involving public facilities',
    outside_scope = 'Private construction approvals and technical permitting decisions assigned to authorized offices\nMatters primarily concerning transport operations, environmental policy, or budgeting unless directly related to a public works proposal\nNational infrastructure projects outside local legislative authority',
    notes = 'This description is a general local legislative scope reference. Technical design, procurement, and project administration remain subject to the applicable offices, standards, and approval processes.'
WHERE jurisdiction_id = 4;

UPDATE jurisdictions
SET
    scope_definition = 'Matters concerning environmental protection, waste management, pollution prevention, conservation, and local policies that promote a clean, resilient, and sustainable community.',
    covered_areas = 'Solid waste management and cleanliness\nPollution prevention and control\nWater, air, and land protection\nConservation of natural resources and green spaces\nClimate resilience and environmental awareness',
    primary_responsibilities = 'Review local environmental policies and programs\nExamine reports on waste management, pollution, and environmental conditions\nConsider measures that protect natural resources and public spaces\nReview community environmental education and conservation initiatives\nAssess environmental considerations in proposed local policies and projects',
    typical_legislative_matters = 'Solid waste management ordinances\nEnvironmental protection and cleanliness measures\nPollution control policies\nConservation and green-space programs\nResolutions concerning climate resilience, environmental education, and sustainable local practices',
    outside_scope = 'Technical environmental permits and enforcement actions assigned to authorized environmental offices\nMatters primarily concerning health, infrastructure, or public safety unless there is a direct environmental component\nNational environmental regulation and protected-area decisions outside local legislative authority',
    notes = 'This is a general legislative scope description for local reference and does not replace national environmental laws, agency regulations, technical assessments, or official permitting requirements.'
WHERE jurisdiction_id = 5;
