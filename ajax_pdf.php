<?php
/*
$log_file = "php.log";
error_reporting(E_ALL);
ini_set('log_errors', TRUE);
ini_set('error_log', $log_file);
*/

include 'yhteys.php';

$nytaika = time();
$nyt = date("Ymd_His", $nytaika); 

  
$mita = $_POST['mita'];


//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
//require_once('tcpdf_include.php'); 

// LOAD THE TCPDF CLASS AND CONFIGURATION
require_once('tcpdf/config/tcpdf_config.php');
require_once('tcpdf/tcpdf.php'); 

// Extend the TCPDF class to create custom Header and Footer 
class MYPDF extends TCPDF {
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('freesans', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Sivu '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function Header() {

        include 'yhteys.php';

        $kategoria = $_POST['kategoria'];
        $rooli = $_POST['rooli'];    

        if ($kategoria == 1)
            $kat = "Koulutussuunnittelu";
        else if ($kategoria == 2)
            $kat = "Markkinointiviestintä";    
        else if ($kategoria == 3)
            $kat = "Opiskelijavalinta";  
        else if ($kategoria == 4)
            $kat = "Koulutuksen aloitus";  
        else if ($kategoria == 5)
            $kat = "Puuttuvan osaamisen hankkiminen";  
        else if ($kategoria == 6)
            $kat = "Arviointi ja osaamisen todentaminen"; 
        else if ($kategoria == 7)
            $kat = "Koulutuksen järjestäjän tuki- ja yhteistyöprosessit";   
        else
            $kat = "Kategoriaa ei ole valittu";

        // haetaan roolit
        $hakur = $yhteys_pdo->prepare("SELECT nro, rooli FROM $tietokanta.roolit ORDER BY jarjestys");
        $hakur->execute();
        while ($hr = $hakur->fetch(PDO::FETCH_ASSOC))
        {
            $rooli_nro = $hr['nro'];
            $rooli_rooli[$rooli_nro] = $hr['rooli'];
        }

        $roo = $rooli_rooli[$rooli];

        if ($roo == "")
            $roo = "Roolia ei ole valittu";


        /*
        if ($rooli == 1)
            $roo = "Hakija";
        else if ($rooli == 2)
            $roo = "Opiskelija / huoltaja";
        else if ($rooli == 3)
            $roo = "Vastuuopettaja";
        else if ($rooli == 4)
            $roo = "Opo";
        else if ($rooli == 5)
            $roo = "Opintosihteeri";
        else if ($rooli == 6)
            $roo = "Koulutuspäällikkö";
        else
            $roo = "Roolia ei ole valittu";
        */

        $hteksti = $kat." / ".$roo;


        if ($this->tocpage) {
            // *** replace the following parent::Header() with your code for TOC page
            $this->SetFont('freesans', '', 7);
            // Title
            $this->Cell(0, 15, $hteksti, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    
        } else {
            // *** replace the following parent::Header() with your code for normal pages
            $this->SetFont('freesans', '', 7);
            // Title
            $this->Cell(0, 15, $hteksti, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        }
    }

}
// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('TAI');
$pdf->SetTitle('Turun ammatti-instituutti');
$pdf->SetSubject('Laatukäsikirja');
$pdf->SetKeywords('TCPDF, PDF');

// set header and footer fonts
$pdf->setHeaderFont(Array('freesans', '', 6));
$pdf->setFooterFont(Array('freesans', '', 6));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(15, PDF_MARGIN_TOP, 15);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);



// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// freesans or arial to reduce file size.
$pdf->SetFont('freesans', '', 10, '', true);


$sivunumero = 1;

// Add a page
// This method has several options, check the source code documentation for more information.


$style = array(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

$style4 = array('width' => 0.1, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));

$style5 = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));



if ($_POST['toiminto'] == "pdf")
{
    $html = "<html><head><style>
    body {
        color: #000;
        font-size: 9px;
    }
 
    p {
        font-size: 9px;
    }  
    span {
        font-size: 9px; 
    }
    li {
        font-size: 9px; 
    }
  
    </style><body>";

    $pdf->writeHTML($html, true, false, true, false, '');
    
    $kategoria = $_POST['kategoria'];
    $rooli = $_POST['rooli'];    
    $mita = $_POST['mita'];

    if ($mita > 0)
    {

        $pdf->AddPage();

        if ($mita == 1) // tulostetaan valittu kategoria ja rooli
        {    
            $sisallys = $yhteys_pdo->prepare("SELECT distinct s.id, s.paa, s.taso1, s.taso2, s.otsikko 
            FROM $tietokanta.sisallysluettelo s
            RIGHT JOIN $tietokanta.sis si ON si.otsikkoid = s.id
            WHERE si.rooli = :rooli AND si.kategoria = :kategoria
            order by s.paa, s.taso1, s.taso2");
            $sisallys->execute(array(':rooli'=>$rooli, ':kategoria'=>$kategoria));
        }
        else // tulostetaan kaikki
        {
            $sisallys = $yhteys_pdo->prepare("SELECT distinct s.id, s.paa, s.taso1, s.taso2, s.otsikko 
            FROM $tietokanta.sisallysluettelo s
            LEFT JOIN $tietokanta.sis si ON si.otsikkoid = s.id
            order by s.paa, s.taso1, s.taso2");
            $sisallys->execute();            
        }
    
        $myfile = fopen("pdfloki.txt", "a") or die("Unable to open file!");

        $b=0;

        while ($si = $sisallys->fetch(PDO::FETCH_ASSOC))
        {
            // haetaan ja palautetaan otsikon alla oleva sisältö
            $luettelo = $yhteys_pdo->prepare("SELECT id, sisalto FROM $tietokanta.sis WHERE otsikkoid = :otsikko");
            $luettelo->execute(array(':otsikko'=>$si['id']));

            $lu = $luettelo->fetch(PDO::FETCH_ASSOC);            

           
            $sis = $lu['sisalto'];
            
            
            if ($si['taso1'] == '' OR $si['taso2'] == '')
            {
                $taso = 0;
            }
            else
            {
                $taso = 1;
            }                 

            $sis = str_replace("<div>", "<p>", $sis);
            $sis = str_replace("</div>", "</p>", $sis);

            if ($b == 0)
            {
                $loki = $html.$si['otsikko'].$lu['sisalto'];
            }
            else
            {
                $loki = $si['otsikko'].$sis;
            }
                    
            fwrite($myfile, $loki);

            $sections[] = array(
                'title' => $si['otsikko'],
                'content' => $sis,
                'level' => $taso,
            );
            $b++;
        }

        fclose($myfile);
        /*
        https://stackoverflow.com/questions/50578949/how-to-create-dynamic-table-of-content-section-using-tcpdf-with-content-items-as
        */

        //$sections['content'].="</body";

        //Now we'll take our fake sections and add pages/content as needed.
        
        foreach($sections as $section) {

            $headertag = 'h1';
            if(empty($section['level'])) {
            //Both not set and value of 0 will evaluate true here.
            //I'm adding new pages for any top-level section here, but you don't need to.
            //$pdf->addPage();
            $level = 0;
            $fsize = "14px";
            } else {
            //Any non-zero level header I'll give an h2.
            $headertag = 'h2';
            $level = $section['level'];
            $fsize = "12px";
            }
            //We add a bookmark right before we start our output for the section copy.
            $bookmark_style = $level > 0 ? 'I' : 'B'; //Make subheading italic.
            $pdf->Bookmark($section['title'], $level, -1, '', $bookmark_style, $level_colors[$level], -1, '');
            //See below for some notes on the Bookmark method.
        
            //Then we output our content.
            $pdf->WriteHTML("<{$headertag} style=\"line-height: 40px; font-size: {$fsize}\">".htmlspecialchars($section['title'], ENT_COMPAT, 'UTF-8').
            "</{$headertag}> {$section['content']}");
        }
    



        // add a new page for TOC
        $pdf->addTOCPage();

        // write the TOC title and/or other elements on the TOC page
        $pdf->SetFont('freesans', 'B', 12);
        $pdf->MultiCell(0, 0, 'Sisällysluettelo', 0, 'C', 0, 1, '', '', true, 0);
        $pdf->Ln();
        $pdf->SetFont('freesans', '', 8);

        // define styles for various bookmark levels
        $bookmark_templates = array();

        /*
        * The key of the $bookmark_templates array represent the bookmark level (from 0 to n).
        * The following templates will be replaced with proper content:
        *     #TOC_DESCRIPTION#    this will be replaced with the bookmark description;
        *     #TOC_PAGE_NUMBER#    this will be replaced with page number.
        *
        * NOTES:
        *     If you want to align the page number on the right you have to use a monospaced font like arial, otherwise you can left align using any font type.
        *     The following is just an example, you can get various styles by combining various HTML elements.
        */

        // A monospaced font for the page number is mandatory to get the right alignment
        $bookmark_templates[0] = '<table border="0" cellpadding="0" cellspacing="0" style="background-color:#EEFAFF"><tr><td width="155mm"><span style="font-family:arial;font-weight:bold;font-size:11px;color:black;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:arial;font-weight:bold;font-size:11px;color:black;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[1] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="5mm">&nbsp;</td><td width="150mm"><span style="font-family:arial;font-size:11px;color:green;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:arial;font-weight:bold;font-size:11px;color:green;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[2] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="10mm">&nbsp;</td><td width="145mm"><span style="font-family:arial;font-size:10px;color:#666666;"><i>#TOC_DESCRIPTION#</i></span></td><td width="25mm"><span style="font-family:arial;font-weight:bold;font-size:10px;color:#666666;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        // add other bookmark level templates here ...

        // add table of content at page 1
        // (check the example n. 45 for a text-only TOC
        $pdf->addHTMLTOC(1, 'INDEX', $bookmark_templates, true, 'B', array(128,0,0));

        // end of TOC page
        $pdf->endTOCPage();

        // reset pointer to the last page
        $pdf->lastPage();

        $html = "</body></html>";

        $pdf->writeHTML($html, true, false, true, false, '');

        $tallennuspolku = $_SERVER['DOCUMENT_ROOT'] . 'tai/laatu/pdf/raportti_'.$nyt.'.pdf';

        $pdf->Output($tallennuspolku, 'F');

        
    }

    $polku = 'https://suomenitratkaisut.fi/tai/laatu/pdf/raportti_'.$nyt.'.pdf';
    $palautus['tpolku'] = $tallennuspolku;
    $palautus['polku'] = $polku;
    echo json_encode($palautus);
    exit;
}