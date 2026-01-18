<?php
// =================================================================
// PHP LOGIC: OVERALL DASHBOARD DATA PDF BACKUP USING FPDF
// =================================================================

// Ensure fpdf.php is in the root directory
require('fpdf/fpdf.php'); 
require_once 'db_connect.php';

// --- FPDF Class Extension for Headers/Footers ---
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Overall Ledger Backup', 0, 1, 'C');
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

// --- 1. Fetch ALL Profiles and Summary Data ---
$profiles = [];
$overall_collected = 0;
$overall_pending = 0;

$sql = "
    SELECT 
        p.id, 
        p.name, 
        p.mobile,
        COALESCE(SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END), 0) AS total_credit,
        COALESCE(SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END), 0) AS total_debit
    FROM 
        profiles p
    LEFT JOIN 
        transactions t ON p.id = t.profile_id
    GROUP BY 
        p.id, p.name, p.mobile
    ORDER BY 
        p.name ASC
";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $row['balance'] = $row['total_credit'] - $row['total_debit'];
        if ($row['balance'] > 0) {
            $overall_collected += $row['balance'];
        } elseif ($row['balance'] < 0) {
            $overall_pending += abs($row['balance']);
        }
        $profiles[] = $row;
    }
    $result->free();
}

// --- 2. FPDF Generation ---
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

// Summary Box
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 230, 255); // Light Blue Fill
$pdf->Cell(0, 8, 'Overall Financial Summary', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 7, 'Overall Receivable (Collected):', 1, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 7, 'Rs. ' . number_format($overall_collected, 2), 1, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 7, 'Overall Payable (Pending/Advance):', 1, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 7, 'Rs. ' . number_format($overall_pending, 2), 1, 1);

$pdf->Ln(10);

// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 220, 255); // Light Blue Header
$pdf->Cell(50, 7, 'Client Name (Mobile)', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Total Credit', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Total Debit', 1, 0, 'C', true);
$pdf->Cell(60, 7, 'Net Balance (Status)', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 9);
foreach ($profiles as $p) {
    $balance_label = ($p['balance'] >= 0) ? ' (Receivable)' : ' (Payable)';
    $balance_display = number_format($p['balance'], 2) . $balance_label;

    $pdf->Cell(50, 6, htmlspecialchars($p['name']) . ' (' . htmlspecialchars($p['mobile']) . ')', 1, 0);
    $pdf->Cell(40, 6, 'Rs. ' . number_format($p['total_credit'], 2), 1, 0, 'R');
    $pdf->Cell(40, 6, 'Rs. ' . number_format($p['total_debit'], 2), 1, 0, 'R');
    $pdf->Cell(60, 6, 'Rs. ' . $balance_display, 1, 1, 'R');
}

// Output the PDF
$pdf->Output('I', 'Overall_Ledger_Backup_' . date('Ymd') . '.pdf');

?>