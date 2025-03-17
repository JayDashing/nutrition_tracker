<?php
// Create a new file called generate_pdf.php in the same directory as terms.php
// This script will generate a one-page PDF file from the Terms and Conditions

// Include the TCPDF library
require_once('vendor/composer/TCPDF-main/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('NutriTrack');
$pdf->SetAuthor('NutriTrack');
$pdf->SetTitle('NutriTrack Terms and Conditions');
$pdf->SetSubject('Terms and Conditions');
$pdf->SetKeywords('NutriTrack, Terms, Conditions, Agreement');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont('courier');

// Set margins - reduced to maximize space
$pdf->SetMargins(10, 10, 10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 10);

// Add a page
$pdf->AddPage();

// Set font - smaller size to fit on one page
$pdf->SetFont('helvetica', '', 8);

// HTML content - streamlined for one page
$html = '
<h1 style="color: #2a6f3f; text-align: center; font-size: 16px; margin-bottom: 6px;">NutriTrack Terms & Conditions</h1>
<p style="text-align: center; font-size: 9px; margin-top: 0;">Last Updated: February 28, 2025</p>

<p style="font-size: 9px; color: #1b5e20; font-weight: bold; margin-bottom: 4px;">Welcome to NutriTrack! These Terms and Conditions govern your access to and use of our nutrition tracking services. By creating an account and using our platform, you agree to comply with and be bound by these terms.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">1. Account Registration</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">To use NutriTrack, you must create a personal account with accurate information. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account. NutriTrack reserves the right to suspend accounts that violate these terms.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">2. Privacy & Data Protection</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">Your privacy is important to us. NutriTrack collects and processes personal information in compliance with the <strong>Data Privacy Act of 2012</strong>. We implement appropriate security measures to protect your data. For details, please refer to our Privacy Policy.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">3. Acceptable Use</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">NutriTrack is for personal health and nutrition management. Users agree not to: Use the service for unlawful purposes; attempt unauthorized access; upload harmful content; impersonate others; share accounts with multiple users.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">4. Intellectual Property</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">All content, features, and functionality of NutriTrack are owned by NutriTrack and protected by copyright, trademark, and other intellectual property laws.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">5. Limitation of Liability</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">NutriTrack provides nutritional information for general wellness purposes only. We do not provide medical advice. Always consult healthcare professionals before making significant changes to your diet or exercise regimen.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">6. Modifications to Terms</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">NutriTrack may update these terms periodically. We will notify users of material changes. Your continued use of NutriTrack after modifications constitutes acceptance of updated terms.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">7. Termination</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">NutriTrack reserves the right to suspend or terminate your access to our services, with or without notice, for conduct that violates these Terms or for any other reason at our discretion.</p>

<h2 style="color: #2a6f3f; font-size: 11px; margin-bottom: 3px; margin-top: 6px;">8. Contact Information</h2>
<p style="font-size: 9px; margin-top: 0; margin-bottom: 4px;">If you have questions about these Terms, please contact our support team at <strong>nutritrack2025@gmail.com</strong>.</p>

<p style="font-size: 9px; text-align: center; margin-top: 6px;">By using NutriTrack, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.</p>
';

// Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('NutriTrack_Terms_and_Conditions.pdf', 'D');