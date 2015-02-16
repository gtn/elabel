<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Collection of useful functions and constants
 *
 * @package    block_elabel
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author	   Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once dirname(__FILE__)."/inc.php";

global $DB, $USER, $COURSE,$labelconfig,$CFG;
require_once $CFG->libdir . '/pdflib.php';
$requestid = required_param('request', PARAM_INT);

$request = $DB->get_record('block_elabel_request', array('id' => $requestid));
if(!$request || $request->state != STATUS_GRANTED)
	print_error('notgranted','block_elabel');

$total = block_elabel_get_score_for_request($request);

if($total >= $labelconfig->labelprofessional)
	$class = "label_professional";
elseif($total >= $labelconfig->labeladvanced)
$class = "label_advanced";
else
	$class = "label_none";

if($class == "label_none") {
	echo get_string('label_none_text','block_elabel');
	die;
}
block_elabel_init_js_css();

$html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>Label</title>
<link href="style.css" rel="stylesheet" type="text/css" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">

/*<![CDATA[*/
img.c2 {
margin: 0;
padding: 0;
border: 0;
}

div.c1 {
clear: both;
}
/*]]>*/

</style>
</head>

<body class="print">
<div id="exaLabel">
<div id="exaLabel-Award">

<div class="exaLabel-AwardLogo">
<img src="pix/logo.jpg" alt="" />
</div>

<h1>E-Learning Label</h1>

<p>Dem Antrag auf Vergabe eines E-Learning Labels für den Lehrgang</p>

<p class="exaLabel-Award-Course">'.$request->officialcoursename.'</p>

<p>Jhg '.$request->year.', SKZ '.$request->coursenumber. '</p>

<p>wird stattgegeben.</p>
<br />
<p>Der gegenständliche Lehrgang kann mit dem Label</p>

<div class="exaLabel-AwardLabel">
<img src="'.get_string($class.'_pic','block_elabel').'" alt="" />
</div>

<p>gekennzeichnet werden.</p>

<br />

<p>'.get_string($class.'_text','block_elabel').'</p>

<br /> <br />

<p class="exaLabel-City">Krems, '.date("d.m.Y",$request->timecreated).'</p>

<br /> <br /> <br />
<table class="exaLabel-AwardDate">
<tr>
<td>
<hr>
<br /> Dipl.-Ing. Dr. Erwin Bratengeyer <br /> E-Learning Center
</td>
<td>
<hr>
<br /> Mag. Dr. Brigitte Hahn, MAS <br /> Qualitätsmanagement und
Lehrentwicklung
</td>
</tr>
</table>
</div>
</div>
</body>
</html>';

echo $html;die;
/*
 // create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFOoter(false);
$pdf->setJPEGQuality(75);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->AddPage();
$pdf->Image('pix/logo.jpg',75,10,50,50,'JPEG');

$pdf->SetFont('helvetica', 'B', 25, '', 'false');
$pdf->Text(65, 70, 'E-Learning Label');

$pdf->SetFont('helvetica', '', 10, '', 'false');
$pdf->SetXY(165, 90);
$pdf->writeHTML('
		<p>Dem Antrag auf Vergabe eines E-Learning Labels für den Lehrgang</p>
		<p class="exaLabel-Award-Course">name</p>
		<p>Jhg WS2015 SKZ 123</p>
		<p>wird stattgegeben</p>');

$pdf->Output('label.pdf');*/