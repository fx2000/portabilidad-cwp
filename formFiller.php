<?php
/**
 *
 * Portabilidad Numérica en Campo
 *
 * @copyright     Copyright (c) Móviles de Panamá, S.A. (http://www.movilesdepanama.com)
 * @link          http://portabilidad.appstic.net Portabilidad Numérica en Campo Project
 * @package       Script
 * @since         Portabilidad Numérica en Campo(tm) v 0.1a
 */

// Load dependencies
require_once "vendor/autoload.php";

include "config.php";

use mikehaertl\pdftk\Pdf;

class formFiller {

	/*
	 * Grabs data from ODK Aggregate, fills PDF forms and emails them to a predefined recepient
	 */
	public function fill() {

		// Logging class initialization
		$logger = new Katzgrau\KLogger\Logger(DIR . '/logs');

		// Connect to MySQL database
		$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
		if ($db->connect_errno > 0) {
			die('Unable to connect to database [' . $db->connect_error . ']');
			$logger->error('Unable to connect to database [' . $db->connect_error . ']');
		}

		// Get unprocessed records from ODK Aggregate
		$sql =
			"SELECT *
				FROM " . MYSQL_TABLE .
				" WHERE processed = 0";

		if (!$result = $db->query($sql)) {
    		die('There was an error running the query [' . $db->error . ']');
    		$logger->error('There was an error running the query [' . $db->error . ']');
		}

		$numRecords = $result->num_rows;

		// If there are unprocessed records on the list
		if ($numRecords > 0) {

			$result->data_seek(0);
			while ($row = $result->fetch_assoc()) {

				// Create directory for form files
				mkdir('processed/' . $row['LINEA_NUM_PORTAR0']);

				// Trim extra characters for URL
				$trimUri = substr($row['_URI'], 5);

				// Save copy of Cedula or Pasaporte and convert to pdf
				exec("sudo wget -O processed/" . $row['LINEA_NUM_PORTAR0'] . "/Cedula.jpg --http-user=aggregate --http-password=romsDaniel2012 " . HOST . "view/binaryData?blobKey=portabilidad%5B%40version%3Dnull+and+%40uiVersion%3Dnull%5D%2Fportabilidad%5B%40key%3Duuid%3A" . $trimUri . "%5D%2FcedulaFoto");
				exec("sudo convert processed/" . $row['LINEA_NUM_PORTAR0'] . "/Cedula.jpg processed/" . $row['LINEA_NUM_PORTAR0'] . "/Cedula-" . $row['LINEA_NUM_PORTAR0'] . ".pdf");

				// Save customer's signature convert to pdf and turn into stamp
				exec("sudo wget -O processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.jpg --http-user=aggregate --http-password=romsDaniel2012 " . HOST . "view/binaryData?blobKey=portabilidad%5B%40version%3Dnull+and+%40uiVersion%3Dnull%5D%2Fportabilidad%5B%40key%3Duuid%3A" . $trimUri . "%5D%2FsignatureClient");
				exec("sudo convert processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.jpg processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.pdf");
				exec("sudo pdfjam --paper 'letter' --scale 0.35 --offset '5cm -6cm' --outfile processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp1.pdf processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.pdf");

				// Save customer's signature, convert to pdf and turn into stamp
				exec("sudo wget -O processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.jpg --http-user=aggregate --http-password=romsDaniel2012 " . HOST . "view/binaryData?blobKey=portabilidad%5B%40version%3Dnull+and+%40uiVersion%3Dnull%5D%2Fportabilidad%5B%40key%3Duuid%3A" . $trimUri . "%5D%2FsignatureCoordinador");
				exec("sudo convert processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.jpg processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.pdf");
				exec("sudo pdfjam --paper 'letter' --scale 0.35 --offset '-5cm -6cm' --outfile processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp2.pdf processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.pdf");

				// Load form
				$pdf = new Pdf('portabilidad.pdf');

				// Fill form with data array and save filled form
				$pdf->fillForm(
					array(
						'fecha'          => substr($row['DATE'], 0, -9),
						'nip'            => $row['NIP'],
						'nombre'         => $row['USUARIO_NOMBRE'],
						'cedula'         => $row['USUARIO_CEDULA'],
						'barriada'       => $row['DIRECCION_BARRIADA'],
						'calle'          => $row['DIRECCION_CALLE'],
						'edificio'       => $row['DIRECCION_EDIFICIO'],
						'numero'         => $row['DIRECCION_NUMERO'],
						'telefono'       => $row['CONTACTO_TELEFONO'],
						'email'          => $row['CONTACTO_EMAIL'],
						'numPortar0'     => $row['LINEA_NUM_PORTAR0'],
						'cantidadPortar' => '1',
						'requiereMasNo'  => 'X',
						'operadora'      => $row['LINEA_OPERADORA'],
						'numCuenta'      => $row['LINEA_NUM_CUENTA'],
						'cedulaPromotor' => $row['COORDINADOR_CEDULA_COORDINADOR'],
					)
				)
					->needAppearances()
					->saveAs('processed/' . $row['LINEA_NUM_PORTAR0'] . '/Formulario.pdf')
				;

				// Stamp pdf form with customer's signature
				exec("sudo pdftk processed/" . $row['LINEA_NUM_PORTAR0'] . "/Formulario.pdf stamp processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp1.pdf output processed/" . $row['LINEA_NUM_PORTAR0'] . "/FormularioPre.pdf");

				// Stamp pdf form with coordinator's signature
				exec("sudo pdftk processed/" . $row['LINEA_NUM_PORTAR0'] . "/FormularioPre.pdf stamp processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp2.pdf output processed/" . $row['LINEA_NUM_PORTAR0'] . "/Formulario-" . $row['LINEA_NUM_PORTAR0'] . ".pdf");

				// Delete leftover files
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/Cedula.jpg");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.jpg");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCliente.pdf");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.jpg");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/FirmaCoordinador.pdf");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/Formulario.pdf");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/FormularioPre.pdf");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp1.pdf");
				exec("sudo rm processed/" . $row['LINEA_NUM_PORTAR0'] . "/stamp2.pdf");

				// Generate account activation email
				$mail = new PHPMailer(true);

				// Set PHP Mailer parameters
				$mail->isSMTP();
				$mail->Host = EMAIL_SERVER;
				$mail->SMTPAuth = true;
				$mail->Username = EMAIL_USER;
				$mail->Password = EMAIL_PASSWORD;
				$mail->From = EMAIL_FROM;
				$mail->FromName = EMAIL_SENDER_NAME;
				$mail->addAddress(EMAIL_CWP);
				$mail->Port = 465;
				$mail->Timeout = 30;
				$mail->WordWrap = 50;
				$mail->isHTML(true);
				$mail->addAttachment('processed/' . $row['LINEA_NUM_PORTAR0'] . '/Formulario-' . $row['LINEA_NUM_PORTAR0'] . '.pdf');
				$mail->addAttachment('processed/' . $row['LINEA_NUM_PORTAR0'] . '/Cedula-' . $row['LINEA_NUM_PORTAR0'] . '.pdf');
				$mail->CharSet = "UTF-8";
				$mail->Subject = 'Nuevo registro de Portabilidad Numérica (NIP: ' . $row['NIP'] . ')';
				$mail->Body =
					'<html>
					<body>
						<div style="font-family:Tahoma;">
							Se anexa el formulario de portabilidad numérica del número ' . $row['LINEA_NUM_PORTAR0'] . '</br>
							correspondiente al NIP ' . $row['NIP'] . '.</br>
							Se les agradece realizar las gestiones necesarias. Si existe algún problema,</br>
							por favor escribir a <a href=\"mailto:daniel.duque@movilesdepanama.com\">daniel.duque@movilesdepanama.com</a></br>
							o llamar al <b>+507 388-6220</b><br/><br/>
							Gracias,<br/><br/>
							<b>Móviles de Panamá, S.A.</b>
						</div>
					</body>
					<html>';

				// Insert processed tag
				$sql2 =
					"UPDATE " . MYSQL_TABLE .
						" SET processed = 0 WHERE _URI = " . "\"" . $row['_URI'] . "\"";

				if (!$result2 = $db->query($sql2)) {
		    		die('There was an error running the query [' . $db->error . ']');
		    		$logger->error('There was an error running the query [' . $db->error . ']');
				}
			}
			return $numRecords;

		// Otherwise, return 0
		} else {
			return $numRecords;
		}
	}
}

// Call Form Filler class and excecute function
$formFiller = new formFiller();
$records = $formFiller->fill();

// Logging class initialization
$logger = new Katzgrau\KLogger\Logger(DIR . '/logs');

// write message to the log file and display message on screen
if (isset($records)) {
	echo $records . ' record(s) were processed correctly';
	$logger->info($records . ' record(s) were processed correctly');
} else {
	echo 'Error processing record(s): No result was received from formFiller Class';
	$logger->error('formFiller.php - Error processing record(s): No result was received from formFiller Class');
}
