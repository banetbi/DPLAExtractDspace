<?php
/**
 * Created by IntelliJ IDEA.
 * User: jcdalton
 * Date: 3/8/18
 * Time: 9:35 AM
 */
require_once('vendor/autoload.php');

use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;

class PullMetadata {

  protected $arrRequiredCollections;// = array('10288/13881', '10288/22221', '10288/20417');

  protected $objOaiEndpoint;// = Endpoint::build('https://digitalarchive.wm.edu/oai/request');

  public function __construct(array $arrRequiredCollections, Endpoint $objOaiEndpoint) {
    $this->arrRequiredCollections = $arrRequiredCollections;
    $this->objOaiEndpoint = $objOaiEndpoint;
  }

  public function PullMetadata() {
    foreach ($this->arrRequiredCollections as $strCollectionHandle) {

      $strFormatedHandle = 'col_' . str_replace('/', '_', $strCollectionHandle);
      print($strFormatedHandle);
      $arrResult = $this->objOaiEndpoint->listRecords('qdc', NULL, NULL, $strFormatedHandle);
      foreach ($arrResult as $objResult) {
        //print_r($objResult->header->identifier);
        $arrIdentifierParts = explode(':', $objResult->header->identifier);

        //print_r($arrIdentifierParts);
        //var_dump($objResult);
        try {
          $recordURL = 'http://' . $arrIdentifierParts[1] . '/rest/handle/' . $arrIdentifierParts[2] . '?expand=metadata';
          $recordResult = json_decode(file_get_contents($recordURL));
          //$recordResult = $this->objOaiEndpoint->getRecord((string) $objResult->header->identifier, 'qdc');
          //print_r($recordResult);
        } catch
        (Exception $e) {
          print("Error: " . $e->getMessage() . "\n");
          continue;
        }
        $strFileName = str_replace('/', '_', $objResult->header->identifier);
        $strFileName = str_replace(':', '.', $strFileName);
        $arrStrCollectionHandle = explode('/', $arrIdentifierParts[sizeof($arrIdentifierParts) - 1]);

        $strCollectionHandleEnd = $arrStrCollectionHandle[sizeof($arrStrCollectionHandle) - 1];
        print($strCollectionHandleEnd . " is collection handle ending.\n");

        if($strCollectionHandleEnd == "") {
          print("No handle !\n");
          continue;
        }

        $xmlFile = fopen("export/$strFileName.xml", 'w');
        fputs($xmlFile, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fputs($xmlFile, '<?xml-model href="dplava.xsd" type="application/xml" schematypens="http://purl.oclc.org/dsdl/schematron"?>' . "\n");
        fputs($xmlFile, '<mdRecord xmlns="http://dplava.lib.virginia.edu" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:edm="http://www.europeana.eu/schemas/edm/" xsi:schemaLocation="http://dplava.lib.virginia.edu/dplava.xsd">' . "\n");
        fputs($xmlFile, '<dcterms:rights>http://rightsstatements.org/vocab/NoC-US/1.0/</dcterms:rights>' . "\n");
        fputs($xmlFile, '<dcterms:provenance>College of William &amp; Mary</dcterms:provenance>' . "\n");
        fputs($xmlFile, '<dcterms:medium>nonprojected graphic</dcterms:medium>' . "\n");
        $boolIsShownAt = false;

        foreach($recordResult->metadata as $objMetadata) {
          switch($objMetadata->key) {
            case 'dc.contributor.author':
              print('Author is ' . $objMetadata->value . "\n");
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:creator>" . $objMetadata->value . "</dcterms:creator>\n");
              }
              break;
            case 'dc.title':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:title>" . htmlspecialchars($objMetadata->value, ENT_XML1, 'UTF-8') . "</dcterms:title>\n");
              }
              break;
            case 'dc.contributor.author':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:creator>" . $objMetadata->value . "</dcterms:creator>\n");
              }
              break;
            case 'dc.description.abstract':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:description>" . htmlspecialchars($objMetadata->value, ENT_XML1, 'UTF-8') . "</dcterms:description>\n");
              }
              break;
            case 'dc.date.issued':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:created>" . $objMetadata->value . "</dcterms:created>\n");
              }
              break;
            case 'dc.type':
              if($objMetadata->value != '') {

                fputs($xmlFile, "<dcterms:type>" . ucwords($objMetadata->value) . "</dcterms:type>\n");
              }
              break;
            case 'dc.identifier.uri':
              if(!$boolIsShownAt && $objMetadata->value != '') {

                fputs($xmlFile, "<edm:isShownAt>" . $objMetadata->value . "</edm:isShownAt>\n");
                $boolIsShownAt = true;
              }
              break;
            case 'dc.identifier.other':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:identifier>" . $objMetadata->value . "</dcterms:identifier>\n");
              }
              break;
            case 'dc.identifier.collectionId':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:identifier>" . $objMetadata->value . ' ' . $strCollectionHandleEnd . "</dcterms:identifier>\n");
              }
              break;
            case 'dc.language':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:language>" . $objMetadata->value . "</dcterms:language>\n");
              }
              break;
            case 'dc.language.iso':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:language>" . $objMetadata->value . "</dcterms:language>\n");
              }
              break;
            case 'dc.relation.ispartof':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:isPartOf>" . $objMetadata->value . "</dcterms:isPartOf>\n");
              }
              break;
            case 'dcterms.isPartOf':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:isPartOf>" . $objMetadata->value . "</dcterms:isPartOf>\n");
              }
              break;
            case 'dc.publisher':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:publisher>" . htmlspecialchars($objMetadata->value, ENT_XML1, 'UTF-8') . "</dcterms:publisher>\n");
              }
              break;
            case 'dc.subject':
              if($objMetadata->value != '') {
                fputs($xmlFile, "<dcterms:subject>" . $objMetadata->value . "</dcterms:subject>\n");
              }
          }
        }
        $bitstreamURL = 'http://' . $arrIdentifierParts[1] . '/rest/handle/' . $arrIdentifierParts[2] . '?expand=bitstreams';
        $queryData = json_decode(file_get_contents($bitstreamURL));
        //print_r($queryData);
        $boolOriginal = false;
        $boolThumbnail = false;
        foreach ($queryData->bitstreams as $objBitstream) {
          if ($objBitstream->bundleName == 'THUMBNAIL' && !$boolThumbnail && $objBitstream->retrieveLink != '') {
            fputs($xmlFile, '<edm:preview>https://digitalarchive.wm.edu' . $objBitstream->retrieveLink . '</edm:preview>' . "\n");
            $boolThumbnail = true;
            //print($objBitstream->retrieveLink . "\n");
            break;
          }
          if($objBitstream->bundleName == 'ORIGINAL' && !$boolOriginal && $objBitstream->format != '') {
            //fputs($xmlFile, "<dc:format>" . $objBitstream->format . "</dc:format>\n");
            $boolOriginal = true;
          }
        }
        fputs($xmlFile, "</mdRecord>\n");
        fclose($xmlFile);

      }
    }
  }
}

$objMetaData = new PullMetadata(array('10288/20417'), Endpoint::build('https://digitalarchive.wm.edu/oai/request'));
$objMetaData->PullMetadata();
