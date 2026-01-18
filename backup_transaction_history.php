<?php
// =================================================================
// PHP LOGIC: INDIVIDUAL PROFILE TRANSACTION HISTORY PDF BACKUP USING FPDF
// =================================================================

// ⚠️ FIX: The file MUST be named 'fpdf.php' and placed in the SAME directory
//        as this script for this 'require' statement to work.
require('fpdf/fpdf.php');
require_once 'db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Profile ID required.");
}

$profile_id = intval($_GET['id']);
$profile = [];
$transactions = [];
$total_credit = 0;
$total_debit = 0;
$balance = 0;

// --- FPDF Class Extension for Headers/Footers ---
class PDF_History extends FPDF
{
    protected $profile_name;
    
    function setProfileName($name) {
        $this->profile_name = $name;
    }

    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Transaction History: ' . $this->profile_name, 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// --- 1. Fetch Profile Details and Summary ---
// Fetch Profile Details
$sql_profile = "SELECT id, name, mobile FROM profiles WHERE id = ?";
if ($stmt = $conn->prepare($sql_profile)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $profile = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

if (empty($profile)) {
    die("Error: Profile not found.");
}

// Calculate Summary
$sql_summary = "
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS total_credit,
        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS total_debit
    FROM transactions WHERE profile_id = ?
";
if ($stmt = $conn->prepare($sql_summary)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result()->fetch_assoc();
        $total_credit = $result['total_credit'];
        $total_debit = $result['total_debit'];
        $balance = $total_credit - $total_debit;
    }
    $stmt->close();
}

// Fetch Transaction History
$sql_history = "SELECT * FROM transactions WHERE profile_id = ? ORDER BY transaction_date DESC, created_at DESC";
if ($stmt = $conn->prepare($sql_history)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// --- 2. FPDF Generation ---
$pdf = new PDF_History();
$pdf->setProfileName(htmlspecialchars($profile['name']));
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

$balance_label = ($balance >= 0) ? 'Receivable' : 'Advance/Payable';
$balance_sign = ($balance >= 0) ? '+' : '-';

// Profile Info
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Mobile: ' . htmlspecialchars($profile['mobile']), 0, 1);
$pdf->Ln(2);

// Summary Box
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(200, 230, 255);
$pdf->Cell(0, 7, 'Financial Summary', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(63, 6, 'Total Credit (IN): Rs. ' . number_format($total_credit, 2), 1, 0);
$pdf->Cell(63, 6, 'Total Debit (OUT): Rs. ' . number_format($total_debit, 2), 1, 0);
$pdf->Cell(64, 6, 'Net Balance: ' . $balance_sign . ' Rs. ' . number_format(abs($balance), 2) . ' (' . $balance_label . ')', 1, 1);

$pdf->Ln(5);

// Transaction History Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Type', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Amount (Rs.)', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Method', 1, 0, 'C', true);
$pdf->Cell(80, 7, 'Note', 1, 1, 'C', true);

// Transaction History Data
$pdf->SetFont('Arial', '', 9);
foreach ($transactions as $t) {
    // Note: FPDF doesn't handle multi-line text easily for a simple table row
    $note_display = htmlspecialchars(substr($t['note'], 0, 45));

    $pdf->Cell(25, 6, date('Y-m-d', strtotime($t['transaction_date'])), 1, 0);
    $pdf->Cell(20, 6, ucfirst($t['type']), 1, 0);
    $pdf->Cell(30, 6, number_format($t['amount'], 2), 1, 0, 'R');
    $pdf->Cell(35, 6, htmlspecialchars($t['payment_method']), 1, 0);
    $pdf->Cell(80, 6, $note_display, 1, 1);
}

// Output the PDF
// 'I' means the PDF is displayed in the browser (inline)
// 'D' means the PDF is forced for download
$pdf->Output('I', 'Transaction_History_' . htmlspecialchars($profile['name']) . '_' . date('Ymd') . '.pdf');

?>