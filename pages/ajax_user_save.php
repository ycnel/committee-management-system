<?php
/**
 * pages/ajax_user_save.php
 * ------------------------------------------------------------------
 * Handles CREATE and UPDATE for the `users` table (id=0 means
 * create). Password is required on create, optional on update
 * (blank keeps the existing password).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id       = (int)($_POST['id'] ?? 0);
$fullName = clean($_POST['full_name'] ?? '');
$email    = clean($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$roleId   = (int)($_POST['role_id'] ?? 0);
$status   = clean($_POST['status'] ?? 'Active');
$backgroundFields = [
    'highest_education', 'degree_course', 'school_university',
    'major_specialization', 'certifications_training', 'current_profession',
    'years_experience', 'previous_positions', 'previous_organizations',
    'government_experience', 'primary_expertise', 'secondary_expertise',
    'knowledge_areas', 'relevant_skills', 'committee_expertise',
    'expertise_keywords',
];
$background = [];
foreach ($backgroundFields as $field) {
    $background[$field] = clean($_POST[$field] ?? '');
}
$yearsExperience = $background['years_experience'] === '' ? null : (int)$background['years_experience'];
$background['years_experience'] = $yearsExperience;

$errors = [];
if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($roleId <= 0) $errors[] = 'Please select a role.';
if (!in_array($status, ['Active', 'Inactive'], true)) $errors[] = 'Invalid status value.';
if ($yearsExperience !== null && ($yearsExperience < 0 || $yearsExperience > 100)) $errors[] = 'Years of experience must be between 0 and 100.';
if ($id <= 0 && $password === '') $errors[] = 'Password is required for a new user.';
if ($password !== '') $errors = array_merge($errors, validatePasswordPolicy($password));
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

try {
    $roleCheck = $pdo->prepare('SELECT id FROM roles WHERE id = :id');
    $roleCheck->execute([':id' => $roleId]);
    if (!$roleCheck->fetch()) jsonResponse(false, 'Selected role does not exist.');

    $dupStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id != :id');
    $dupStmt->execute([':email' => $email, ':id' => $id]);
    if ($dupStmt->fetch()) jsonResponse(false, 'A user with this email already exists.');

    if ($id > 0) {
        $before = $pdo->prepare('SELECT id FROM users WHERE id = :id');
        $before->execute([':id' => $id]);
        if (!$before->fetch()) jsonResponse(false, 'User not found.');

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'UPDATE users SET full_name = :name, email = :email, password = :pw, role_id = :role, status = :status WHERE id = :id'
            );
            $stmt->execute([':name' => $fullName, ':email' => $email, ':pw' => $hash, ':role' => $roleId, ':status' => $status, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET full_name = :name, email = :email, role_id = :role, status = :status WHERE id = :id'
            );
            $stmt->execute([':name' => $fullName, ':email' => $email, ':role' => $roleId, ':status' => $status, ':id' => $id]);
        }

        saveUserBackground($pdo, $id, $background);

        logActivity(currentUserId(), 'Update', 'Updated user #' . $id . ' (' . $email . ')');
        jsonResponse(true, 'User updated successfully.', ['id' => $id]);
    }

    // ---- CREATE ----
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password, role_id, status, created_at)
         VALUES (:name, :email, :pw, :role, :status, NOW())'
    );
    $stmt->execute([':name' => $fullName, ':email' => $email, ':pw' => $hash, ':role' => $roleId, ':status' => $status]);
    $id = (int)$pdo->lastInsertId();

    saveUserBackground($pdo, $id, $background);

    logActivity(currentUserId(), 'Insert', 'Created user #' . $id . ' (' . $email . ')');
    jsonResponse(true, 'User created successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('User save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000) {
        jsonResponse(false, 'A user with this email already exists.');
    }
    jsonResponse(false, 'A database error occurred while saving the user.');
}

function saveUserBackground(PDO $pdo, int $userId, array $background): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO user_background
            (user_id, highest_education, degree_course, school_university,
             major_specialization, certifications_training, current_profession,
             years_experience, previous_positions, previous_organizations,
             government_experience, primary_expertise, secondary_expertise,
             knowledge_areas, relevant_skills, committee_expertise, expertise_keywords)
         VALUES
            (:user_id, :highest_education, :degree_course, :school_university,
             :major_specialization, :certifications_training, :current_profession,
             :years_experience, :previous_positions, :previous_organizations,
             :government_experience, :primary_expertise, :secondary_expertise,
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
    $params = ['user_id' => $userId];
    foreach ($background as $field => $value) $params[$field] = $value === '' ? null : $value;
    $stmt->execute($params);
}
