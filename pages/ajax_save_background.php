<?php
/** Save the signed-in user's own education, professional, and expertise profile. */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

function profileBackgroundResponse(bool $success, string $message): void
{
    if (isAjaxRequest()) {
        jsonResponse($success, $message);
    }
    setFlash($success ? 'success' : 'danger', $message);
    redirect(APP_URL . '/pages/profile.php');
}

$fields = [
    'highest_education', 'degree_course', 'school_university', 'major_specialization',
    'certifications_training', 'current_profession', 'previous_positions',
    'previous_organizations', 'government_experience', 'primary_expertise',
    'secondary_expertise', 'knowledge_areas', 'relevant_skills',
    'committee_expertise', 'expertise_keywords',
];
$values = [];
foreach ($fields as $field) {
    $values[$field] = clean($_POST[$field] ?? '');
}

if ($values['highest_education'] === '') {
    profileBackgroundResponse(false, 'Highest Educational Attainment is required.');
}

$yearsExperience = trim((string)($_POST['years_experience'] ?? ''));
if ($yearsExperience !== '' && (!ctype_digit($yearsExperience) || (int)$yearsExperience > 100)) {
    profileBackgroundResponse(false, 'Years of experience must be a whole number between 0 and 100.');
}
$values['years_experience'] = $yearsExperience === '' ? null : (int)$yearsExperience;

$pdo = db();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO user_background
            (user_id, highest_education, degree_course, school_university, major_specialization,
             certifications_training, current_profession, years_experience, previous_positions,
             previous_organizations, government_experience, primary_expertise, secondary_expertise,
             knowledge_areas, relevant_skills, committee_expertise, expertise_keywords)
         VALUES
            (:user_id, :highest_education, :degree_course, :school_university, :major_specialization,
             :certifications_training, :current_profession, :years_experience, :previous_positions,
             :previous_organizations, :government_experience, :primary_expertise, :secondary_expertise,
             :knowledge_areas, :relevant_skills, :committee_expertise, :expertise_keywords)
         ON DUPLICATE KEY UPDATE
            highest_education = VALUES(highest_education), degree_course = VALUES(degree_course),
            school_university = VALUES(school_university), major_specialization = VALUES(major_specialization),
            certifications_training = VALUES(certifications_training), current_profession = VALUES(current_profession),
            years_experience = VALUES(years_experience), previous_positions = VALUES(previous_positions),
            previous_organizations = VALUES(previous_organizations), government_experience = VALUES(government_experience),
            primary_expertise = VALUES(primary_expertise), secondary_expertise = VALUES(secondary_expertise),
            knowledge_areas = VALUES(knowledge_areas), relevant_skills = VALUES(relevant_skills),
            committee_expertise = VALUES(committee_expertise), expertise_keywords = VALUES(expertise_keywords)'
    );
    $params = [':user_id' => currentUserId()];
    foreach ($values as $field => $value) {
        $params[':' . $field] = $value;
    }
    $stmt->execute($params);
    try {
        logActivity(currentUserId(), 'Update', 'Updated own profile background information.');
    } catch (Throwable $e) {
        error_log('Profile background activity log error: ' . $e->getMessage());
    }
    profileBackgroundResponse(true, 'Profile information saved successfully.');
} catch (Throwable $e) {
    error_log('Profile background save error: ' . $e->getMessage());
    profileBackgroundResponse(false, 'A database error occurred while saving your profile information.');
}
