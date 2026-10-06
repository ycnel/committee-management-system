<?php
/**
 * Apply the official 13th City Council Majority/Minority roster to committee memberships.
 *
 * This is deliberately tolerant of the live CMAS naming variations in the database,
 * including honorifics, curly apostrophes, and slight committee-title wording drift.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function normalizeText(string $value): string
{
    $value = @mb_strtolower((string) $value, 'UTF-8');
    $value = str_replace(['“', '”', '’', '‘', '´', '\'', '"', '`'], '', $value);
    $value = preg_replace('/\b(?:hon|hon\.|hon\s+)\b/u', '', $value);
    $value = preg_replace('/\b(?:jr|jr\.)\b/u', '', $value);
    $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function normalizeForMatch(string $value): string
{
    $value = normalizeText($value);
    foreach (['lady', 'dj', 'taga', 'jhun', 'erika', 'fa', 'jong', 'jeff'] as $nickname) {
        $value = preg_replace('/\b' . preg_quote($nickname, '/') . '\b/u', ' ', $value);
    }
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function normalizeCommittee(string $value): string
{
    return normalizeText($value);
}

$groupMap = [
    'Accountability of Public Officers and Investigation (Blue Ribbon)' => [
        'Majority' => ['Raymundo R. Yupangco', 'Louisa Marie J. Quintos-Tan', 'Jaybee S. Hizon', 'John Christopher L. Sy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par', 'Rafael P. Borromeo'],
    ],
    'Amusement, Games and Entertainment' => [
        'Majority' => ['Raymundo R. Yupangco', 'Arlene Maile I. Atienza', 'Rosalino P. Ibay Jr', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Jesus E. Fajardo Jr'],
    ],
    'Appropriations' => [
        'Majority' => ['Raymundo R. Yupangco', 'Roberto S. Espiritu II', 'Louisa Marie J. Quintos-Tan', 'Darwin B. Sia'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par', 'Rafael P. Borromeo'],
    ],
    'Barangay Affairs' => [
        'Majority' => ['Raymundo R. Yupangco', 'Voltaire Carlo D. Castaneda', 'Fernando S. Mercado', 'John Christopher L. Sy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par', 'Jesus E. Fajardo Jr'],
    ],
    'Children\'s Affairs' => [
        'Majority' => ['Raymundo R. Yupangco', 'Francis Michael U. Almiron', 'Roberto S. Espiritu II', 'Joaquin Andre D. Domagoso'],
        'Minority' => ['Don Juan A. Bagatsing', 'Eunice Ann Denice G. Castro', 'Karen C. Alibarbar'],
    ],
    'Civil Service, Appointments and Reorganization' => [
        'Majority' => ['Raymundo R. Yupangco', 'John Christopher L. Sy', 'Mark Anthony A. Ignacio', 'Rosalino P. Ibay Jr'],
        'Minority' => ['Don Juan A. Bagatsing', 'Erick Ian O. Nieva', 'Fernando S. Mercado'],
    ],
    'Consumer\'s Affairs & Price Control' => [
        'Majority' => ['Raymundo R. Yupangco', 'Francis Michael U. Almiron', 'John Christopher L. Sy', 'Christian Paul L. Uy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Fernando S. Mercado', 'Ernesto C. Isip Jr'],
    ],
    'Cooperatives, NGO\'s & People\'s Organizations' => [
        'Majority' => ['Raymundo R. Yupangco', 'Rosalino P. Ibay Jr', 'Joaquin Andre D. Domagoso', 'Edward M. Tan'],
        'Minority' => ['Don Juan A. Bagatsing', 'Fernando S. Mercado', 'Eunice Ann Denice G. Castro'],
    ],
    'Economic Development' => [
        'Majority' => ['Raymundo R. Yupangco', 'Roberto S. Espiritu II', 'Edward M. Tan', 'Mark Anthony A. Ignacio'],
        'Minority' => ['Don Juan A. Bagatsing', 'Mark Ryan B. Ponce', 'Ernesto C. Isip Jr'],
    ],
    'Education' => [
        'Majority' => ['Raymundo R. Yupangco', 'Mark Anthony A. Ignacio', 'Timothy Oliver I. Zarcal', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Juliana Rae M. Ibay', 'Eunice Ann Denice G. Castro'],
    ],
    'Engineering and Public Works' => [
        'Majority' => ['Raymundo R. Yupangco', 'Roberto S. Espiritu II', 'Voltaire Carlo D. Castaneda', 'Joaquin Andre D. Domagoso'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par', 'Jesus E. Fajardo Jr'],
    ],
    'Environmental Protection and Ecological Preservation and Sanitation and Animal Welfare' => [
        'Majority' => ['Raymundo R. Yupangco', 'Arlene Maile I. Atienza', 'Joaquin Andre D. Domagoso', 'Darwin B. Sia'],
        'Minority' => ['Don Juan A. Bagatsing', 'Mark Ryan B. Ponce', 'Rafael P. Borromeo'],
    ],
    'Family Relations and Moral Recovery' => [
        'Majority' => ['Raymundo R. Yupangco', 'Darwin B. Sia', 'Irma C. Alfonso-Juson'],
        'Minority' => ['Don Juan A. Bagatsing', 'Juliana Rae M. Ibay', 'Erick Ian O. Nieva', 'Elmer M. Par'],
    ],
    'Health' => [
        'Majority' => ['Raymundo R. Yupangco', 'Louisa Marie J. Quintos-Tan', 'Luciano M. Veloso', 'Christian Paul L. Uy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Fernando S. Mercado', 'Juliana Rae M. Ibay'],
    ],
    'Housing, Land, Urban Planning Development and Resettlement' => [
        'Majority' => ['Raymundo R. Yupangco', 'Louisa Marie J. Quintos-Tan', 'Mark Anthony A. Ignacio', 'Christian Paul L. Uy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Elmer M. Par'],
    ],
    'Information and Communication Technology' => [
        'Majority' => ['Raymundo R. Yupangco', 'Christian Paul L. Uy', 'John Christopher L. Sy', 'Francis Michael U. Almiron'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Pamela G. Fugoso-Pascual'],
    ],
    'International Relations' => [
        'Majority' => ['Raymundo R. Yupangco', 'Irma C. Alfonso-Juson', 'Ernesto C. Isip Jr', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Eunice Ann Denice G. Castro', 'Fernando S. Mercado'],
    ],
    'Justice and Human Rights' => [
        'Majority' => ['Raymundo R. Yupangco', 'Rosalino P. Ibay Jr', 'Voltaire Carlo D. Castaneda', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Juliana Rae M. Ibay', 'Karen C. Alibarbar'],
    ],
    'Labor, Employment and Human Resources' => [
        'Majority' => ['Raymundo R. Yupangco', 'Voltaire Carlo D. Castaneda', 'Arlene Maile I. Atienza', 'Francis Michael U. Almiron'],
        'Minority' => ['Don Juan A. Bagatsing', 'Eunice Ann Denice G. Castro', 'Juliana Rae M. Ibay'],
    ],
    'Laws' => [
        'Majority' => ['Raymundo R. Yupangco', 'Timothy Oliver I. Zarcal', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Ernesto C. Isip Jr', 'Juliana Rae M. Ibay', 'Elmer M. Par'],
    ],
    'Livelihood' => [
        'Majority' => ['Raymundo R. Yupangco', 'Louisa Marie J. Quintos-Tan', 'Francis Michael U. Almiron', 'Christian Paul L. Uy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Fernando S. Mercado', 'Pamela G. Fugoso-Pascual'],
    ],
    'Markets, Hawkers and Slaughterhouses' => [
        'Majority' => ['Raymundo R. Yupangco', 'Irma C. Alfonso-Juson', 'Voltaire Carlo D. Castaneda', 'Jaybee S. Hizon'],
        'Minority' => ['Don Juan A. Bagatsing', 'Erick Ian O. Nieva', 'Jesus E. Fajardo Jr'],
    ],
    'Migrant Workers' => [
        'Majority' => ['Raymundo R. Yupangco', 'Edward M. Tan', 'Luciano M. Veloso', 'Darwin B. Sia'],
        'Minority' => ['Mark Ryan B. Ponce', 'Elmer M. Par', 'Ernesto C. Isip Jr'],
    ],
    'Oversight' => [
        'Majority' => ['Louisa Marie J. Quintos-Tan', 'Christian Paul L. Uy', 'Timothy Oliver I. Zarcal', 'Roberto S. Espiritu II'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Jesus E. Fajardo Jr'],
    ],
    'Patrimonial Properties' => [
        'Majority' => ['Raymundo R. Yupangco', 'Ernesto C. Isip Jr', 'Roberto S. Espiritu II', 'Mark Anthony A. Ignacio'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Jesus E. Fajardo Jr'],
    ],
    'Police, Peace and Order, Fire and Public Safety' => [
        'Majority' => ['Raymundo R. Yupangco', 'Joaquin Andre D. Domagoso', 'Darwin B. Sia', 'John Christopher L. Sy'],
        'Minority' => ['Don Juan A. Bagatsing', 'Jesus E. Fajardo Jr', 'Mark Ryan B. Ponce'],
    ],
    'Rules and Ethics' => [
        'Majority' => ['Raymundo R. Yupangco', 'Timothy Oliver I. Zarcal', 'Edward M. Tan', 'Voltaire Carlo D. Castaneda'],
        'Minority' => ['Don Juan A. Bagatsing', 'Ernesto C. Isip Jr', 'Fernando S. Mercado'],
    ],
    'Science and Technology' => [
        'Majority' => ['Raymundo R. Yupangco', 'Edward M. Tan', 'Mark Anthony A. Ignacio', 'Luciano M. Veloso'],
        'Minority' => ['Mark Ryan B. Ponce', 'Juliana Rae M. Ibay', 'Jefferson C. Lau'],
    ],
    'Senior Citizens and Differently-Abled Persons' => [
        'Majority' => ['Raymundo R. Yupangco', 'Irma C. Alfonso-Juson', 'John Christopher L. Sy', 'Mark Anthony A. Ignacio'],
        'Minority' => ['Don Juan A. Bagatsing', 'Rafael P. Borromeo', 'Pamela G. Fugoso-Pascual'],
    ],
    'Social Welfare' => [
        'Majority' => ['Raymundo R. Yupangco', 'John Christopher L. Sy', 'Mark Anthony A. Ignacio', 'Francis Michael U. Almiron'],
        'Minority' => ['Don Juan A. Bagatsing', 'Juliana Rae M. Ibay', 'Pamela G. Fugoso-Pascual'],
    ],
    'Tourism, Arts and Culture' => [
        'Majority' => ['Raymundo R. Yupangco', 'Arlene Maile I. Atienza', 'Christian Paul L. Uy', 'Edward M. Tan'],
        'Minority' => ['Don Juan A. Bagatsing', 'Eunice Ann Denice G. Castro', 'Karen C. Alibarbar'],
    ],
    'Trade and Industry' => [
        'Majority' => ['Raymundo R. Yupangco', 'Voltaire Carlo D. Castaneda', 'Francis Michael U. Almiron', 'Timothy Oliver I. Zarcal'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par', 'Juliana Rae M. Ibay'],
    ],
    'Transportation' => [
        'Majority' => ['Raymundo R. Yupangco', 'Rosalino P. Ibay Jr', 'Darwin B. Sia', 'Arlene Maile I. Atienza'],
        'Minority' => ['Don Juan A. Bagatsing', 'Fernando S. Mercado', 'Rafael P. Borromeo'],
    ],
    'Urban Poor' => [
        'Majority' => ['Raymundo R. Yupangco', 'Luciano M. Veloso', 'Voltaire Carlo D. Castaneda', 'Jaybee S. Hizon'],
        'Minority' => ['Don Juan A. Bagatsing', 'Mark Ryan B. Ponce', 'Karen C. Alibarbar'],
    ],
    'Utility and Franchise' => [
        'Majority' => ['Raymundo R. Yupangco', 'Mark Anthony A. Ignacio', 'Edward M. Tan', 'Arlene Maile I. Atienza'],
        'Minority' => ['Don Juan A. Bagatsing', 'Jesus E. Fajardo Jr', 'Erick Ian O. Nieva'],
    ],
    'Ways and Means' => [
        'Majority' => ['Raymundo R. Yupangco', 'Jaybee S. Hizon', 'Louisa Marie J. Quintos-Tan', 'Edward M. Tan'],
        'Minority' => ['Elmer M. Par', 'Rafael P. Borromeo', 'Jesus E. Fajardo Jr'],
    ],
    'Women' => [
        'Majority' => ['Raymundo R. Yupangco', 'Luciano M. Veloso', 'Louisa Marie J. Quintos-Tan', 'Arlene Maile I. Atienza'],
        'Minority' => ['Don Juan A. Bagatsing', 'Juliana Rae M. Ibay', 'Mark Ryan B. Ponce'],
    ],
    'Youth Values Formation and Sports Development' => [
        'Majority' => ['Raymundo R. Yupangco', 'Timothy Oliver I. Zarcal', 'Louisa Marie J. Quintos-Tan', 'Joaquin Andre D. Domagoso', 'Jaybee S. Hizon'],
        'Minority' => ['Don Juan A. Bagatsing', 'Elmer M. Par'],
    ],
];

$pdo = db();
$pdo->prepare('UPDATE committee_members SET political_group = NULL WHERE status = "Active"')->execute();

$committeeRows = $pdo->query('SELECT committee_id, committee_name FROM committees')->fetchAll(PDO::FETCH_ASSOC);
$committeeLookup = [];
foreach ($committeeRows as $row) {
    $committeeLookup[normalizeCommittee($row['committee_name'])] = (int) $row['committee_id'];
}

$updatedCount = 0;
$missingCommittees = [];
$missingNames = [];

foreach ($groupMap as $committeeName => $groups) {
    $targetKey = normalizeCommittee($committeeName);
    if (!isset($committeeLookup[$targetKey])) {
        $missingCommittees[] = $committeeName;
        continue;
    }

    $committeeId = $committeeLookup[$targetKey];
    $memberStmt = $pdo->prepare(
        'SELECT cm.committee_member_id, u.full_name
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         WHERE cm.committee_id = :cid AND cm.status = "Active"'
    );
    $memberStmt->execute([':cid' => $committeeId]);
    $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

    $lookup = [];
    foreach ($groups as $group => $names) {
        foreach ($names as $name) {
            $lookup[normalizeForMatch($name)] = $group;
        }
    }

    foreach ($members as $member) {
        $normalizedName = normalizeForMatch($member['full_name']);
        if (!isset($lookup[$normalizedName])) {
            $missingNames[] = $committeeName . ' | ' . $member['full_name'];
            continue;
        }

        $setStmt = $pdo->prepare('UPDATE committee_members SET political_group = :group WHERE committee_member_id = :id');
        $setStmt->execute([
            ':group' => $lookup[$normalizedName],
            ':id' => (int) $member['committee_member_id'],
        ]);
        $updatedCount++;
    }
}

$missingCommittees = array_values(array_unique($missingCommittees));
$missingNames = array_values(array_unique($missingNames));

echo "Updated committee membership political groups: {$updatedCount}\n";
if ($missingCommittees) {
    echo "Missing committees: " . count($missingCommittees) . PHP_EOL;
    foreach (array_slice($missingCommittees, 0, 20) as $m) {
        echo "- {$m}\n";
    }
}
if ($missingNames) {
    echo "Unmatched members: " . count($missingNames) . PHP_EOL;
    foreach (array_slice($missingNames, 0, 20) as $m) {
        echo "- {$m}\n";
    }
}

