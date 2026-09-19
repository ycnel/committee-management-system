<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid user id.');

$pdo = db();
$stmt = $pdo->prepare(
	'SELECT u.id, u.full_name, u.email, u.role_id, u.status,
			ub.highest_education, ub.degree_course, ub.school_university,
			ub.major_specialization, ub.certifications_training,
			ub.current_profession, ub.years_experience, ub.previous_positions,
			ub.previous_organizations, ub.government_experience,
			ub.primary_expertise, ub.secondary_expertise, ub.knowledge_areas,
			ub.relevant_skills, ub.committee_expertise, ub.expertise_keywords
	 FROM users u
	 LEFT JOIN user_background ub ON ub.user_id = u.id
	 WHERE u.id = :id'
);
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) jsonResponse(false, 'User not found.');

jsonResponse(true, '', ['user' => $user]);
