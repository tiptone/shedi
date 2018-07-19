<?php
namespace Shedi;

use Zend_Pdf;
use Zend_Pdf_Page;
use Zend_Pdf_Color_Html;
use Zend_Pdf_Font;
use JMS\Serializer\SerializerBuilder;

class Transcript
{
    public static function splitFile($infile, $outdir)
    {
        if (!file_exists($infile)) {
            throw new \Exception("File not found: $infile");
        }

        if (!is_writable($outdir)) {
            throw new \Exception("Not writable: $outdir");
        }

        $currentState = 0;
        $nextState    = 0;

        $startOver = false;

        $transcript = null;
        $sendingInstitution = null;
        $individualIdentification = null;
        $name = null;
        $studentAwards = null;
        $testScoreRecord = null;
        $subTest = null;
        $address = null;
        $summary = null;
        $detail = null;
        $sessionHeader = null;
        $academicSummary = null;
        $academicStatus = null;
        $courseRecord = null;
        $degreeRecord = null;
        $marksAwarded = null;

        $lines = file($infile);

        $lineNumber = 1;

        $tokens = self::getTokens();
        $fsa    = self::getStateTable();

        foreach ($lines as $line) {
            $delimiter = '|';

            $line = trim($line);
            list($token, $rest) = explode($delimiter, $line, 2);

            if (!in_array($token, $tokens)) {
                trigger_error("Unknown Token [$token] on line $lineNumber");
                exit(1);
            }

            $rest = explode($delimiter, $rest);

            $nextState = $fsa[$currentState][$token];

            switch ($nextState) {
                case 0:
                    // just finished an ISA/IEA block
                    $startOver = true;
                    break;

                case 1:
                    // ISA
                    // nothing to do
                    break;

                case 2:
                    // GS
                    // nothing to do
                    break;

                case 3:
                    // ST
                    $transcript = new \stdClass();
                    $transcript->st = new \stdClass();
                    $transcript->st->code = $rest[0];
                    $transcript->st->controlNumber = $rest[1];
                    break;

                case 4:
                    // BGN
                    $transcript->bgn = new \stdClass();
                    $transcript->bgn->purposeCode = $rest[0];
                    $transcript->bgn->referenceId = $rest[1];
                    $transcript->bgn->date = $rest[2];
                    $transcript->bgn->time = $rest[3];
                    if (isset($rest[4])) {
                        $transcript->bgn->timeCode = $rest[4];
                    }
                    break;

                case 5:
                    // ERP
                    $transcript->erp = new \stdClass();
                    $transcript->erp->typeCode = $rest[0];
                    $transcript->erp->statusReasonCode = $rest[1];
                    break;

                case 6:
                    // REF
                    $tmp = new \stdClass();
                    $tmp->referenceIdQualifier = $rest[0];
                    $tmp->referenceId = $rest[1];
                    if (isset($rest[2])) {
                        $tmp->description = $rest[2];
                    }
                    $transcript->refs[] = $tmp;
                    $tmp = null;
                    break;

                case 7:
                    // DMG
                    $transcript->dmg = new \stdClass();
                    switch ($rest[0]) {
                        case 'CM':
                            $transcript->dmg->dateTimeFormatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $transcript->dmg->dateTimeFormatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $transcript->dmg->dateTimeFormatQualifier = 'CCYYMMDD';
                            break;
                        case 'DB':
                            $transcript->dmg->dateTimeFormatQualifier = 'MMDDCCYY';
                            break;
                        case 'MD':
                            $transcript->dmg->dateTimeFormatQualifier = 'MMDD';
                            break;
                    }
                    $transcript->dmg->dateOfBirth = $rest[1];
                    if (isset($rest[2])) {
                        $transcript->dmg->genderCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $transcript->dmg->maritalStatusCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $transcript->dmg->raceEthnicityCode = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $transcript->dmg->citizenshipCode = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $transcript->dmg->countryCode = $rest[6];
                    }
                    break;

                case 8:
                    // LUI
                    $tmp = new \stdClass();
                    $tmp->languageCodeQualifier = $rest[0];
                    $tmp->languageCode = $rest[1];
                    $tmp->languageName = $rest[2];
                    if (isset($rest[3])) {
                        $tmp->useOfLanguageIndicator = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->languageProficiencyIndicator = $rest[4];
                    }
                    $transcript->luis[] = $tmp;
                    $tmp = null;
                    break;

                case 9:
                    // IND
                    $tmp = new \stdClass();
                    $tmp->countryOfBirth = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->stateOfBirth = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->countyDesignator = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->cityName = $rest[3];
                    }
                    $transcript->inds[] = $tmp;
                    $tmp = null;
                    break;

                case 10:
                    // DTP
                    $transcript->dtp = new \stdClass();
                    $transcript->dtp->dateTimeQualifier = $rest[0];
                    $transcript->dtp->dateTimePeriodQualifier = $rest[1];
                    $transcript->dtp->dateTimePeriod = $rest[2];
                    break;

                case 11:
                    // RAP
                    $tmp = new \stdClass();
                    $tmp->requirementCode = $rest[0];
                    $tmp->mainCategory = $rest[1];
                    if (isset($rest[2])) {
                        $tmp->lesserCategory = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->usageIndicator = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->requirementMet = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->dateTimePeriodFormatQualifier = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->dateTimePeriod = $rest[6];
                    }
                    $transcript->raps[] = $tmp;
                    $tmp = null;
                    break;

                case 12:
                    // PCL
                    $tmp = new \stdClass();
                    $tmp->identificationCodeQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->identificationCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->dateTimePeriodFormatQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->datesAttended = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->academicDegreeCode = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->dateDegreeConferred = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->description = $rest[6];
                    }
                    $transcript->pcls[] = $tmp;
                    $tmp = null;
                    break;

                case 13:
                    // NTE
                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];
                    $transcript->ntes[] = $tmp;
                    $tmp = null;
                    break;

                case 14:
                    // N1
                    if (is_object($sendingInstitution)) {
                        $transcript->n1s[] = $sendingInstitution;
                    }

                    $sendingInstitution = new \stdClass();
                    $sendingInstitution->n1 = new \stdClass();
                    $sendingInstitution->n1->entityIdentifierCode = $rest[0];
                    $sendingInstitution->n1->name = $rest[1];
                    $sendingInstitution->n1->identificationCodeQualifier = $rest[2];
                    $sendingInstitution->n1->identificationCode = $rest[3];
                    break;

                case 15:
                    // N1::N2
                    $sendingInstitution->n2 = new \stdClass();
                    $sendingInstitution->n2->name01 = $rest[0];
                    if (isset($rest[1])) {
                        $sendingInstitution->n2->name02 = $rest[1];
                    }
                    break;

                case 16:
                    // N1::N3
                    $sendingInstitution->n3 = new \stdClass();
                    $sendingInstitution->n3->address01 = $rest[0];
                    if (isset($rest[1])) {
                        $sendingInstitution->n3->address02 = $rest[1];
                    }
                    break;

                case 17:
                    // N1::N4
                    $sendingInstitution->n4 = new \stdClass();
                    $sendingInstitution->n4->cityName = $rest[0];
                    $sendingInstitution->n4->stateCode = $rest[1];
                    $sendingInstitution->n4->postalCode = $rest[2];
                    if (isset($rest[3])) {
                        $sendingInstitution->n4->countryCode = $rest[3];
                    }
                    break;

                case 18:
                    // N1::PER
                    $sendingInstitution->per = new \stdClass();
                    $sendingInstitution->per->contactFunctionCode = $rest[0];
                    if (isset($rest[1])) {
                        $sendingInstitution->per->name = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $sendingInstitution->per->communicationNumberQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $sendingInstitution->per->communicationNumber = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $sendingInstitution->per->communicationNumberQualifier2 = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $sendingInstitution->per->communicationNumber = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $sendingInstitution->per->communicationNumberQualifier3 = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $sendingInstitution->per->communicationNumber3 = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $sendingInstitution->per->contactInquiryReference = $rest[8];
                    }
                    break;

                case 19:
                    // IN1
                    // has to have had at least one N1
                    $transcript->n1s[] = $sendingInstitution;
                    $sendingInstitution = null;

                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }

                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    $individualIdentification = new \stdClass();
                    $individualIdentification->in1 = new \stdClass();
                    $individualIdentification->in1->entityTypeQualifier = $rest[0];
                    $individualIdentification->in1->nameTypeCode = $rest[1];
                    if (isset($rest[2])) {
                        $individualIdentification->in1->entityIdentifierCode = $rest[2];
                    }
                    break;

                case 20:
                    // IN1::IN2
                    if (!is_object($name)) {
                        $name = new \stdClass();
                    }

                    switch ($rest[0]) {
                        case '01':
                            $name->prefix = $rest[1];
                            break;
                        case '02':
                            $name->firstName = $rest[1];
                            break;
                        case '03':
                            $name->firstMiddleName = $rest[1];
                            break;
                        case '04':
                            $name->secondMiddleName = $rest[1];
                            break;
                        case '05':
                            $name->lastName = $rest[1];
                            break;
                        case '06':
                            $name->firstInitial = $rest[1];
                            break;
                        case '07':
                            $name->firstMiddleInitial = $rest[1];
                            break;
                        case '08':
                            $name->secondMiddleInitial = $rest[1];
                            break;
                        case '09':
                            $name->suffix = $rest[1];
                            break;
                        case '12':
                            $name->combinedName = $rest[1];
                            break;
                        case '14':
                            $name->agencyName = $rest[1];
                            break;
                        case '15':
                            $name->maidenName = $rest[1];
                            break;
                        case '16':
                            $name->compositeName = $rest[1];
                            break;
                        case '17':
                            $name->middleNames = $rest[1];
                            break;
                        case '18':
                            $name->preferredFirstName = $rest[1];
                            break;
                        case '22':
                            $name->organizationName = $rest[1];
                            break;
                        default:
                            $name->name = $rest[1];
                            break;
                    }
                    break;

                case 21:
                    // IN1::N3
                    if (is_object($name)) {
                        $individualIdentification->in2[] = $name;
                        $name = null;
                    }

                    if (is_object($address)) {
                        $individualIdentification->n3s[] = $address;
                        $address = null;
                    }

                    $address = new \stdClass();
                    $address->n3 = new \stdClass();
                    $address->n3->address1 = $rest[0];
                    if (isset($rest[1])) {
                        $address->n3->address2 = $rest[1];
                    }
                    break;

                case 22:
                    // IN1::PER
                    if (is_object($name)) {
                        $individualIdentification->in2[] = $name;
                        $name = null;
                    }

                    if (is_object($address)) {
                        $individualIdentification->n3s[] = $address;
                        $address = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->contactFunctionCode = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->name = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->communicatioNumberQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->communicationNumber = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->communicatioNumberQualifier2 = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->communicationNumber2 = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->communicatioNumberQualifier3 = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $tmp->communicationNumber3 = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $tmp->contactInquiryReference = $rest[8];
                    }
                    $individualIdentification->pers[] = $tmp;
                    $tmp = null;
                    break;

                case 23:
                    // IN1::NTE
                    if (is_object($name)) {
                        $individualIdentification->in2[] = $name;
                        $name = null;
                    }

                    if (is_object($address)) {
                        $individualIdentification->n3s[] = $address;
                        $address = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];
                    $individualIdentification->nte = $tmp;
                    $tmp = null;
                    break;

                case 24:
                    // SST
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }

                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }
                    $academicStatus = new \stdClass();
                    $academicStatus->sst = new \stdClass();
                    switch ($rest[0]) {
                        case 'B17':
                            $academicStatus->sst->highSchoolGraduationType = 'Did not complete secondary school';
                            break;
                        case 'B18':
                            $academicStatus->sst->highSchoolGraduationType = 'Standard high school diploma';
                            break;
                        case 'B19':
                            $academicStatus->sst->highSchoolGraduationType = 'Advanced or honors diploma';
                            break;
                        case 'B20':
                            $academicStatus->sst->highSchoolGraduationType = 'Vocational diploma';
                            break;
                        case 'B21':
                            $academicStatus->sst->highSchoolGraduationType = 'Special education diploma';
                            break;
                        case 'B22':
                            $academicStatus->sst->highSchoolGraduationType = 'Certificate of completion or attendance';
                            break;
                        case 'B23':
                            $academicStatus->sst->highSchoolGraduationType = 'Special certificate of completion';
                            break;
                        case 'B24':
                            $academicStatus->sst->highSchoolGraduationType = 'General Education Development Diploma (GED)';
                            break;
                        case 'B25':
                            $academicStatus->sst->highSchoolGraduationType = 'Other high school equivalency diploma';
                            break;
                        case 'B26':
                            $academicStatus->sst->highSchoolGraduationType = 'International diploma or certificate';
                            break;
                        default:
                            // intentionally blank
                            break;
                    }
                    $academicStatus->sst->highSchoolGraduationTypeCode = $rest[0];
                    if (isset($rest[1])) {
                        switch ($rest[1]) {
                            case 'CM':
                                $academicStatus->sst->highSchoolGraduationDateFormat = 'CCYYMM';
                                break;
                            case 'CY':
                                $academicStatus->sst->highSchoolGraduationDateFormat = 'CCYY';
                                break;
                            case 'D8':
                                $academicStatus->sst->highSchoolGraduationDateFormat = 'CCYYMMDD';
                                break;
                            case 'DB':
                                $academicStatus->sst->highSchoolGraduationDateFormat = 'MMDDCCYY';
                                break;
                            default:
                                // intentionally blank
                                break;
                        }
                    }
                    if (isset($rest[3])) {
                        $academicStatus->sst->highSchoolGraduationDate = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $academicStatus->sst->eligibleToReturnCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $academicStatus->sst->dateOfEligibilityToReturnDateFormat = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $academicStatus->sst->dateOfEligibitlityToReturn = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $academicStatus->sst->currentEnrollmentStatusCode = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $academicStatus->sst->studentGradeLevel = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $academicStatus->sst->residencyCode = $rest[8];
                    }
                    break;

                case 25:
                    // ATV
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }
                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    if (is_object($academicStatus)) {
                        $transcript->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($studentAwards)) {
                        $transcript->atvs[] = $studentAwards;
                        $studentAwards = null;
                    }

                    $studentAwards = new \stdClass();
                    $studentAwards->atv = new \stdClass();
                    $studentAwards->atv->awardCodeQualifier = $rest[0];
                    $studentAwards->atv->awardCode = $rest[1];
                    $studentAwards->atv->awardName = $rest[2];
                    break;

                case 26:
                    // TST
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }
                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    if (is_object($academicStatus)) {
                        $transcript->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($studentAwards)) {
                        $transcript->atvs[] = $studentAwards;
                        $studentAwards = null;
                    }

                    if (is_object($testScoreRecord)) {
                        if (is_object($subTest)) {
                            $testScoreRecord->sbts[] = $subTest;
                            $subTest = null;
                        }
                        $transcript->tsts[] = $testScoreRecord;
                        $testScoreRecord = null;
                    }

                    $testScoreRecord = new \stdClass();
                    $testScoreRecord->tst = new \stdClass();
                    $testScoreRecord->tst->testCode = $rest[0];
                    $testScoreRecord->tst->testName = $rest[1];
                    $testScoreRecord->tst->testAdministeredDateFormat = $rest[2];
                    $testScoreRecord->tst->testAdministeredDate = $rest[3];
                    if (isset($rest[4])) {
                        $testScoreRecord->tst->testForm = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $testScoreRecord->tst->testLevel = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $testScoreRecord->tst->studentGradeLevel = $rest[6];
                    }
                    break;

                case 27:
                    // SUM
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }
                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    if (is_object($academicStatus)) {
                        $transcript->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($studentAwards)) {
                        $transcript->atvs[] = $studentAwards;
                        $studentAwards = null;
                    }

                    if (is_object($testScoreRecord)) {
                        if (is_object($subTest)) {
                            $testScoreRecord->sbts[] = $subTest;
                            $subTest = null;
                        }
                        $transcript->tsts[] = $testScoreRecord;
                        $testScoreRecord = null;
                    }

                    if (is_object($summary)) {
                        $transcript->sums[] = $summary;
                        $summary = null;
                    }

                    $summary = new \stdClass();
                    $summary->sum = new \stdClass();
                    $summary->sum->creditTypeCode = $rest[0];
                    $summary->sum->gradeOrCourseLevelCode = $rest[1];
                    $summary->sum->cumulativeSummaryIndicator = $rest[2];
                    $summary->sum->creditHoursIncluded = $rest[3];
                    $summary->sum->creditHoursAttempted = $rest[4];
                    $summary->sum->creditHoursEarned = $rest[5];
                    if (isset($rest[6])) {
                        $summary->sum->lowestPossibleGradePointAverage = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $summary->sum->highestPossibleGradePointAverage = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $summary->sum->gradePointAverage = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $summary->sum->excessiveGpaIndicator = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $summary->sum->classRank = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $summary->sum->quantity = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $summary->sum->dateTimePeriodFormatQualifier = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $summary->sum->dateTimePeriod = $rest[13];
                    }
                    if (isset($rest[14])) {
                        $summary->sum->daysAttended = $rest[14];
                    }
                    if (isset($rest[15])) {
                        $summary->sum->daysAbsent = $rest[15];
                    }
                    if (isset($rest[16])) {
                        $summary->sum->qualityPointsUsedToCalculateGpa = $rest[16];
                    }
                    break;

                case 28:
                    // LX
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2[] = $name;
                            $name = null;
                        }
                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }
                        $transcript->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    if (is_object($academicStatus)) {
                        $transcript->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($studentAwards)) {
                        $transcript->atvs[] = $studentAwards;
                        $studentAwards = null;
                    }

                    if (is_object($testScoreRecord)) {
                        if (is_object($subTest)) {
                            $testScoreRecord->sbts[] = $subTest;
                            $subTest = null;
                        }
                        $transcript->tsts[] = $testScoreRecord;
                        $testScoreRecord = null;
                    }

                    if (is_object($summary)) {
                        $transcript->sums[] = $summary;
                        $summary = null;
                    }

                    $detail = new \stdClass();
                    $detail->lx = new \stdClass();
                    $detail->lx->assignedNumber = $rest[0];
                    break;

                case 29:
                    // IN1::N3::N4
                    $address->n4 = new \stdClass();
                    $address->n4->city = $rest[0];
                    $address->n4->state = $rest[1];
                    $address->n4->postalCode = $rest[2];
                    if (isset($rest[3])) {
                        $address->n4->countryCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $address->n4->locationQualifier = $rest[4];
                    }
                    $individualIdentification->n3s[] = $address;
                    $address = null;
                    break;

                case 30:
                    // SST::SSE
                    $academicStatus->sse = new \stdClass();
                    $academicStatus->sse->entryDate = $rest[0];
                    $academicStatus->sse->exitDate = $rest[1];
                    if (isset($rest[2])) {
                        $academicStatus->sse->reasonCode = $rest[2];
                    }
                    break;

                case 31:
                    // SST::N1
                    $academicStatus->n1 = new \stdClass();
                    $academicStatus->n1->entityIdentifierCode = $rest[0];
                    $academicStatus->n1->name = $rest[1];
                    if (isset($rest[2])) {
                        $academicStatus->n1->institutionCodeQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $academicStatus->n1->institutionCode = $rest[3];
                    }
                    break;

                case 32:
                    // SST::N3
                    $academicStatus->n3 = new \stdClass();
                    $academicStatus->n3->address1 = $rest[0];
                    if (isset($rest[1])) {
                        $academicStatus->n3->address2 = $rest[1];
                    }
                    break;

                case 33:
                    // SST::N4
                    $academicStatus->n4 = new \stdClass();
                    $academicStatus->n4->city = $rest[0];
                    $academicStatus->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $academicStatus->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $academicStatus->n4->countryCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $academicStatus->n4->locationQualifier = $rest[4];
                    }
                    break;

                case 34:
                    // ATV::DTP
                    $tmp = new \stdClass();

                    $tmp->dateTimeQualifier = $rest[0];
                    $tmp->dateTimePeriodFormatQualifier = $rest[1];
                    $tmp->dateTimePeriod = $rest[2];

                    $studentAwards->dtps[] = $tmp;
                    $tmp = null;
                    break;

                case 35:
                    // TST::SBT
                    if (is_object($subTest)) {
                        $testScoreRecord->sbts[] = $subTest;
                        $subTest = null;
                    }

                    $subTest = new \stdClass();
                    $subTest->sbt = new \stdClass();
                    $subTest->sbt->code = $rest[0];
                    if (isset($rest[1])) {
                        $subTest->sbt->name = $rest[1];
                    }
                    break;

                case 36:
                    // SUM::NTE
                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];

                    $summary->ntes[] = $tmp;
                    $tmp = null;
                    break;

                case 37:
                    // LX::IMM
                    $imm = new \stdClass();
                    $imm->typeCode = $rest[0];
                    $imm->dateTimePeriodFormatQualifier = $rest[1];
                    $imm->dateTimePeriod = $rest[2];
                    $imm->statusCode = $rest[3];
                    $imm->reportTypeCode = $rest[4];

                    $detail->imm = $imm;
                    $imm = null;
                    break;

                case 38:
                    // LX::SES
                    if (is_object($sessionHeader)) {
                        if (is_object($academicSummary)) {
                            $sessionHeader->sums[] = $academicSummary;
                            $academicSummary = null;
                        }

                        if (is_object($courseRecord)) {
                            if (is_object($marksAwarded)) {
                                $courseRecord->mkss[] = $marksAwarded;
                                $marksAwarded = null;
                            }

                            $sessionHeader->crss[] = $courseRecord;
                            $courseRecord = null;
                        }

                        if (is_object($degreeRecord)) {
                            $detail->degs[] = $degreeRecord;
                            $degreeRecord = null;
                        }

                        $detail->sess[] = $sessionHeader;
                        $sessionHeader = null;
                    }

                    $sessionHeader = new \stdClass();
                    $sessionHeader->ses = new \stdClass();
                    $sessionHeader->ses->startDate = $rest[0];
                    $sessionHeader->ses->count = $rest[1];
                    $sessionHeader->ses->schoolYear = $rest[2];
                    $sessionHeader->ses->sessionCode = $rest[3];
                    $sessionHeader->ses->name = $rest[4];
                    if (isset($rest[5])) {
                        $sessionHeader->ses->startDateFormat = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $sessionHeader->ses->startDate = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $sessionHeader->ses->endDateFormat = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $sessionHeader->ses->endDate = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $sessionHeader->ses->gradeLevel = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $sessionHeader->ses->curriculumCodeQualifier = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $sessionHeader->ses->curriculumCode = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $sessionHeader->ses->curriculumName = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $sessionHeader->ses->reasonCode = $rest[13];
                    }
                    break;

                case 39:
                    // SE
                    if (is_object($sessionHeader)) {
                        if (is_object($academicSummary)) {
                            $sessionHeader->sums[] = $academicSummary;
                            $academicSummary = null;
                        }

                        if (is_object($courseRecord)) {
                            if (is_object($marksAwarded)) {
                                $courseRecord->mkss[] = $marksAwarded;
                                $marksAwarded = null;
                            }

                            $sessionHeader->crss[] = $courseRecord;
                            $courseRecord = null;
                        }

                        if (is_object($degreeRecord)) {
                            $detail->degs[] = $degreeRecord;
                            $degreeRecord = null;
                        }

                        $detail->sess[] = $sessionHeader;
                        $sessionHeader = null;
                    }

                    $transcript->detail = $detail;
                    $detail = null;

                    $output = json_encode($transcript);
                    $filename = sha1($output);

                    file_put_contents("{$outdir}/{$filename}.json", $output);
                    break;

                case 40:
                    // LX::SES::SSE
                    $sessionHeader->sse = new \stdClass();
                    $sessionHeader->sse->entryDate = $rest[0];
                    if (isset($rest[1])) {
                        $sessionHeader->sse->exitDate = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $sessionHeader->sse->reasonCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $sessionHeader->sse->number = $rest[3];
                    }
                    break;

                case 41:
                    // LX::SES::NTE
                    $sessionHeader->nte = new \stdClass();
                    $sessionHeader->nte->referenceCode = $rest[0];
                    $sessionHeader->nte->description = $rest[1];
                    break;

                case 42:
                    // LX::SES::N1
                    $sessionHeader->n1 = new \stdClass();
                    $sessionHeader->n1->entityIdentifierCode = $rest[0];
                    $sessionHeader->n1->name = $rest[1];
                    if (isset($rest[2])) {
                        $sessionHeader->n1->institutionCodeQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $sessionHeader->n1->institutionCode = $rest[3];
                    }
                    break;

                case 43:
                    // LX::SES::N3
                    $sessionHeader->n3 = new \stdClass();
                    $sessionHeader->n3->address1 = $rest[0];
                    if (isset($rest[1])) {
                        $sessionHeader->n3->address2 = $rest[1];
                    }
                    break;

                case 44:
                    // LX::SES::N4
                    $sessionHeader->n4 = new \stdClass();
                    $sessionHeader->n4->city = $rest[0];
                    $sessionHeader->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $sessionHeader->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $sessionHeader->n4->countryCode = $rest[3];
                    }
                    break;

                case 45:
                    // LX::SES::SUM
                    if (is_object($academicSummary)) {
                        $sessionHeader->sums[] = $academicSummary;
                        $academicSummary = null;
                    }

                    $academicSummary = new \stdClass();
                    $academicSummary->sum = new \stdClass();
                    $academicSummary->sum->creditTypeCode = $rest[0];
                    $academicSummary->sum->gradeOrCourseLevelCode = $rest[1];
                    $academicSummary->sum->cumulativeSummaryIndicator = $rest[2];
                    $academicSummary->sum->creditHoursIncluded = $rest[3];
                    if (isset($rest[4])) {
                        $academicSummary->sum->creditHoursAttempted = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $academicSummary->sum->creditHoursEarned = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $academicSummary->sum->lowestPossibleGradePointAverage = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $academicSummary->sum->highestPossibleGradePointAverage = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $academicSummary->sum->gradePointAverage = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $academicSummary->sum->excessiveGpaIndicator = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $academicSummary->sum->classRank = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $academicSummary->sum->totalNumberOfStudents = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $academicSummary->sum->dateFormatOfClassRanking = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $academicSummary->sum->dateOfClassRanking = $rest[13];
                    }
                    if (isset($rest[14])) {
                        $academicSummary->sum->daysAttended = $rest[14];
                    }
                    if (isset($rest[15])) {
                        $academicSummary->sum->daysAbsent = $rest[15];
                    }
                    if (isset($rest[16])) {
                        $academicSummary->sum->qualityPointsUsedToCalculateGpa = $rest[16];
                    }
                    if (isset($rest[17])) {
                        $academicSummary->sum->summarySource = $rest[17];
                    }
                    break;

                case 46:
                    // LX::SES::CRS
                    if (is_object($academicSummary)) {
                        $sessionHeader->sums[] = $academicSummary;
                        $academicSummary = null;
                    }

                    if (is_object($courseRecord)) {
                        if (is_object($marksAwarded)) {
                            $courseRecord->mkss[] = $marksAwarded;
                            $marksAwarded = null;
                        }

                        $sessionHeader->crss[] = $courseRecord;
                        $courseRecord = null;
                    }

                    $courseRecord = new \stdClass();
                    $courseRecord->crs = new \stdClass();
                    $courseRecord->crs->basisForCredit = $rest[0];
                    $courseRecord->crs->creditTypeCode = $rest[1];
                    $courseRecord->crs->quantity = $rest[2];
                    $courseRecord->crs->creditHoursEarned = $rest[3];
                    $courseRecord->crs->gradeQualifier = $rest[4];
                    $courseRecord->crs->grade = $rest[5];
                    $courseRecord->crs->honorsIndicator = $rest[6];
                    $courseRecord->crs->courseLevel = $rest[7];
                    $courseRecord->crs->courseRepeat = $rest[8];
                    $courseRecord->crs->curriculumCodeQualifier = $rest[9];
                    $courseRecord->crs->curriculumCode = $rest[10];
                    $courseRecord->crs->academicQualityPoints = $rest[11];
                    $courseRecord->crs->courseGradeLevel = $rest[12];
                    $courseRecord->crs->courseSubjectAbbreviation = $rest[13];
                    $courseRecord->crs->courseNumber = $rest[14];
                    if (isset($rest[15])) {
                        $courseRecord->crs->courseTitle = $rest[15];
                    }
                    if (isset($rest[16])) {
                        $courseRecord->crs->daysAttended = $rest[16];
                    }
                    if (isset($rest[17])) {
                        $courseRecord->crs->daysAbsent = $rest[17];
                    }
                    if (isset($rest[18])) {
                        $courseRecord->crs->studentWithdrawlDate = $rest[18];
                    }
                    if (isset($rest[19])) {
                        $courseRecord->crs->courseSourceCode = $rest[19];
                    }
                    break;

                case 47:
                    // LX::SES::DEG
                    if (is_object($academicSummary)) {
                        $sessionHeader->sums[] = $academicSummary;
                        $academicSummary = null;
                    }

                    if (is_object($courseRecord)) {
                        $sessionHeader->crss[] = $courseRecord;
                        $courseRecord = null;
                    }

                    if (is_object($degreeRecord)) {
                        //$sessionHeader->degs[] = $degreeRecord;
                        $detail->degs[] = $degreeRecord;
                        $degreeRecord = null;
                    }

                    $degreeRecord = new \stdClass();
                    $degreeRecord->deg = new \stdClass();
                    $degreeRecord->deg->degreeCode = $rest[0];
                    switch ($rest[0]) {
                        case '2.1':
                            $degreeCodeLabel = 'Postsecondary Certificate Or Diploma (less than one year)';
                            break;
                        case '2.2':
                            $degreeCodeLabel = 'Postsecondary Certificate Or Diploma (one year or more but less than four years)';
                            break;
                        case '2.3':
                            $degreeCodeLabel = 'Associate Degree';
                            break;
                        case '2.4':
                            $degreeCodeLabel = 'Baccalaureate Degree';
                            break;
                        case '2.5':
                            $degreeCodeLabel = 'Baccalaureate (Honours) Degree';
                            break;
                        case '2.6':
                            $degreeCodeLabel = 'Postsecondary Certificate Or Diploma (one year or more but less than two years)';
                            break;
                        case '2.7':
                            $degreeCodeLabel = 'Postsecondary Certificate Or Diploma (two years or more but less than four years)';
                            break;
                        case '3.1':
                            $degreeCodeLabel = 'First Professional Degree';
                            break;
                        case '3.2':
                            $degreeCodeLabel = 'Post-Professional Degree';
                            break;
                        case '4.1':
                            $degreeCodeLabel = 'Graduate Certificate';
                            break;
                        case '4.2':
                            $degreeCodeLabel = "Master's Degree";
                            break;
                        case '4.3':
                            $degreeCodeLabel = 'Intermediate Graduate Degree';
                            break;
                        case '4.4':
                            $degreeCodeLabel = 'Doctoral Degree';
                            break;
                        case '4.5':
                            $degreeCodeLabel = 'Post-Doctoral Degree';
                            break;
                        default:
                            // intentionally blank
                    }
                    switch ($rest[1]) {
                        case 'CM':
                            $degreeRecord->deg->degreeAwardedDateFormat = 'CCYYMM';
                            break;
                        case 'CY':
                            $degreeRecord->deg->degreeAwardedDateFormat = 'CCYY';
                            break;
                        case 'D8':
                            $degreeRecord->deg->degreeAwardedDateFormat = 'CCYYMMDD';
                            break;
                        case 'DB':
                            $degreeRecord->deg->degreeAwardedDateFormat = 'MMDDCCYY';
                            break;
                        default:
                            // intentionally blank
                    }
                    $degreeRecord->deg->degreeAwardedDate = $rest[2];
                    $degreeRecord->deg->title = $rest[3];
                    if (isset($rest[4])) {
                        $degreeRecord->deg->honorsLevel = $rest[4];
                    }
                    break;

                case 48:
                    // GE
                    // nop
                    break;

                case 49:
                    // LX::SES::SUM::NTE
                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];
                    $academicSummary->sum->ntes[] = $tmp;
                    $tmp = null;
                    break;

                case 50:
                    // LX::SES::CRS::REF
                    $tmp = new \stdClass();
                    $tmp->typeOfCourseNumber = $rest[0];
                    $tmp->courseNumber = $rest[1];
                    $tmp->courseTitle = $rest[2];
                    $tmp->referenceIdentifier = $rest[3];

                    $courseRecord->refs[] = $tmp;
                    $tmp = null;
                    break;

                case 51:
                    // LX::SES::CRS::CSU
                    $courseRecord->csu = new \stdClass();
                    $courseRecord->csu->subjectArea = $rest[0];
                    $courseRecord->csu->courseNumber = $rest[1];
                    $courseRecord->csu->startingDateOfCourseFormat = $rest[2];
                    $courseRecord->csu->startingDateOfCourse = $rest[3];
                    $courseRecord->csu->endingDateOfCourseFormat = $rest[4];
                    $courseRecord->csu->endingDateOfCourse = $rest[5];
                    if (isset($rest[6])) {
                        $courseRecord->csu->instructionalSettingCode = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $courseRecord->csu->academicCreditTypeCode = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $courseRecord->csu->quantity = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $courseRecord->csu->courseDuration = $rest[9];
                    }
                    break;

                case 52:
                    // LX::SES::CRS::LUI
                    $tmp = new \stdClass();
                    $tmp->languageCodeQualifier = $rest[0];
                    $tmp->languageCode = $rest[1];
                    $tmp->languageName = $rest[2];
                    $tmp->useOfLanguageIndicator = $rest[3];
                    if (isset($rest[4])) {
                        $tmp->languageProficienceIndicator = $rest[4];
                    }

                    $courseRecord->luis[] = $tmp;
                    $tmp = null;
                    break;

                case 53:
                    // LX::SES::CRS::RAP
                    $tmp = new \stdClass();
                    $tmp->courseRequirement = $rest[0];
                    $tmp->mainCategoryOfRequirement = $rest[1];
                    $tmp->lesserCategoryOfRequirement = $rest[2];
                    if (isset($rest[3])) {
                        $tmp->usageIndicator = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->requirementMet = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->dateStatusAssignedFormat = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->dateStatusAssigned = $rest[6];
                    }

                    $courseRecord->raps[] = $tmp;
                    $tmp = null;
                    break;

                case 54:
                    // LX::SES::CRS::NTE
                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];

                    $courseRecord->ntes[] = $tmp;
                    $tmp = null;
                    break;

                case 55:
                    // LX::SES::CRS::N1
                    $courseRecord->n1 = new \stdClass();
                    $courseRecord->n1->entityIdentifierCode = $rest[0];
                    $courseRecord->n1->name = $rest[1];
                    if (isset($rest[2])) {
                        $courseRecord->n1->institutionCodeQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $courseRecord->n1->institutionCode = $rest[3];
                    }
                    break;

                case 56:
                    // LX::SES::CRS::N4
                    $courseRecord->n4 = new \stdClass();
                    $courseRecord->n4->city = $rest[0];
                    $courseRecord->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $courseRecord->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $courseRecord->n4->countryCode = $rest[3];
                    }
                    break;

                case 57:
                    // LX::SES::CRS::MKS
                    if (is_object($marksAwarded)) {
                        $courseRecord->mkss[] = $marksAwarded;
                        $marksAwarded = null;
                    }

                    $marksAwarded = new \stdClass();
                    $marksAwarded->codeType = $rest[0];
                    $marksAwarded->academicGradeQualifier = $rest[1];
                    $marksAwarded->academicGrade = $rest[2];
                    break;

                case 58:
                    // LX::SES::DEG::SUM
                    $tmp = new \stdClass();
                    $tmp->academicCreditTypeCode = $rest[0];
                    $tmp->academicGrade = $rest[1];
                    $tmp->cumulativeSummaryIndicator = $rest[2];
                    $tmp->academicCreditHoursIncludedInGpa = $rest[3];
                    $tmp->academicCreditHoursAttempted = $rest[4];
                    $tmp->academicCreditHoursEarned = $rest[5];
                    $tmp->lowestPossibleGradePointAverage = $rest[6];
                    $tmp->highestPossibleGradePointAverage = $rest[7];
                    $tmp->gradePointAverage = $rest[8];
                    $tmp->excessiveGpaIndicator = $rest[9];

                    $degreeRecord->sums[] = $tmp;
                    $tmp = null;
                    break;

                case 59:
                    // LX::SES::DEG::FOS
                    $tmp = new \stdClass();
                    $tmp->typeCode = $rest[0];
                    $tmp->codeSet = $rest[1];
                    $tmp->identifictionCode = $rest[2];
                    $tmp->literal = $rest[3];
                    if (isset($rest[4])) {
                        $tmp->honorsLiteral = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->yearsOfStudy = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->gradePointAverage = $rest[6];
                    }

                    $degreeRecord->foss[] = $tmp;
                    $tmp = null;
                    break;

                case 60:
                    // LX::SES::DEG::N1
                    $degreeRecord->n1 = new \stdClass();
                    $degreeRecord->n1->entityIdentifierCode = $rest[0];
                    $degreeRecord->n1->name = $rest[1];
                    $degreeRecord->n1->institutionCodeQualifier = $rest[2];
                    $degreeRecord->n1->institutionCode = $rest[3];
                    break;

                case 61:
                    // LX::SES::DEG::NTE
                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];

                    $degreeRecord->ntes[] = $tmp;
                    $tmp = null;
                    break;

                case 62:
                    // LX::SES::CRS::MKS::LUI
                    $tmp = new \stdClass();
                    $tmp->languageCodeQualifier = $rest[0];
                    $tmp->languageCode = $rest[1];
                    $tmp->nameOfLanguage = $rest[2];
                    $tmp->useOfLanguageIndicator = $rest[3];
                    $tmp->languageProficiencyIndicator = $rest[4];

                    $marksAwarded->lui = $tmp;
                    $tmp = null;
                    break;

                case 63:
                    // TST::SBT::SRE
                    if (!is_object($subTest)) {
                        $subTest = new \stdClass();
                    }
                    $subTest->sre = new \stdClass();
                    $subTest->sre->testScoreType = $rest[0];
                    if (isset($rest[1])) {
                        $subTest->sre->testScore = $rest[1];
                    }
                    break;

                case 64:
                    // TST::SBT::NTE
                    $subTest->nte = new \stdClass();
                    $subTest->nte->referenceCode = $rest[0];
                    $subTest->nte->description = $rest[1];
                    break;

                case 100:
                    trigger_error("BONK [$currentState] [$token] on line $lineNumber", E_USER_ERROR);
                    exit;
            }

            if ($startOver) {
                $currentState = 0;
                $startOver = false;
            } else {
                $currentState = $nextState;
            }

            $lineNumber++;
        }
    }

    private static function getStateTable()
    {
        $fsa = array();

        for ($i=0; $i<=64; $i++) {
            $fsa[$i] = array();
        }

        $fsa[100] = 'ERROR';

        $fsa[0]['ISA'] = 1;   $fsa[1]['ISA'] = 100; $fsa[2]['ISA'] = 100; $fsa[3]['ISA'] = 100; $fsa[4]['ISA'] = 100;
        $fsa[0]['GS']  = 100; $fsa[1]['GS']  = 2;   $fsa[2]['GS']  = 100; $fsa[3]['GS']  = 100; $fsa[4]['GS']  = 100;
        $fsa[0]['ST']  = 100; $fsa[1]['ST']  = 100; $fsa[2]['ST']  = 3;   $fsa[3]['ST']  = 100; $fsa[4]['ST']  = 100;
        $fsa[0]['BGN'] = 100; $fsa[1]['BGN'] = 100; $fsa[2]['BGN'] = 100; $fsa[3]['BGN'] = 4;   $fsa[4]['BGN'] = 100;
        $fsa[0]['ERP'] = 100; $fsa[1]['ERP'] = 100; $fsa[2]['ERP'] = 100; $fsa[3]['ERP'] = 100; $fsa[4]['ERP'] = 5;
        $fsa[0]['REF'] = 100; $fsa[1]['REF'] = 100; $fsa[2]['REF'] = 100; $fsa[3]['REF'] = 100; $fsa[4]['REF'] = 100;
        $fsa[0]['DMG'] = 100; $fsa[1]['DMG'] = 100; $fsa[2]['DMG'] = 100; $fsa[3]['DMG'] = 100; $fsa[4]['DMG'] = 100;
        $fsa[0]['LUI'] = 100; $fsa[1]['LUI'] = 100; $fsa[2]['LUI'] = 100; $fsa[3]['LUI'] = 100; $fsa[4]['LUI'] = 100;
        $fsa[0]['IND'] = 100; $fsa[1]['IND'] = 100; $fsa[2]['IND'] = 100; $fsa[3]['IND'] = 100; $fsa[4]['IND'] = 100;
        $fsa[0]['DTP'] = 100; $fsa[1]['DTP'] = 100; $fsa[2]['DTP'] = 100; $fsa[3]['DTP'] = 100; $fsa[4]['DTP'] = 100;
        $fsa[0]['RAP'] = 100; $fsa[1]['RAP'] = 100; $fsa[2]['RAP'] = 100; $fsa[3]['RAP'] = 100; $fsa[4]['RAP'] = 100;
        $fsa[0]['PCL'] = 100; $fsa[1]['PCL'] = 100; $fsa[2]['PCL'] = 100; $fsa[3]['PCL'] = 100; $fsa[4]['PCL'] = 100;
        $fsa[0]['NTE'] = 100; $fsa[1]['NTE'] = 100; $fsa[2]['NTE'] = 100; $fsa[3]['NTE'] = 100; $fsa[4]['NTE'] = 100;
        $fsa[0]['N1']  = 100; $fsa[1]['N1']  = 100; $fsa[2]['N1']  = 100; $fsa[3]['N1']  = 100; $fsa[4]['N1']  = 100;
        $fsa[0]['N2']  = 100; $fsa[1]['N2']  = 100; $fsa[2]['N2']  = 100; $fsa[3]['N2']  = 100; $fsa[4]['N2']  = 100;
        $fsa[0]['N3']  = 100; $fsa[1]['N3']  = 100; $fsa[2]['N3']  = 100; $fsa[3]['N3']  = 100; $fsa[4]['N3']  = 100;
        $fsa[0]['N4']  = 100; $fsa[1]['N4']  = 100; $fsa[2]['N4']  = 100; $fsa[3]['N4']  = 100; $fsa[4]['N4']  = 100;
        $fsa[0]['PER'] = 100; $fsa[1]['PER'] = 100; $fsa[2]['PER'] = 100; $fsa[3]['PER'] = 100; $fsa[4]['PER'] = 100;
        $fsa[0]['IN1'] = 100; $fsa[1]['IN1'] = 100; $fsa[2]['IN1'] = 100; $fsa[3]['IN1'] = 100; $fsa[4]['IN1'] = 100;
        $fsa[0]['IN2'] = 100; $fsa[1]['IN2'] = 100; $fsa[2]['IN2'] = 100; $fsa[3]['IN2'] = 100; $fsa[4]['IN2'] = 100;
        $fsa[0]['SST'] = 100; $fsa[1]['SST'] = 100; $fsa[2]['SST'] = 100; $fsa[3]['SST'] = 100; $fsa[4]['SST'] = 100;
        $fsa[0]['SSE'] = 100; $fsa[1]['SSE'] = 100; $fsa[2]['SSE'] = 100; $fsa[3]['SSE'] = 100; $fsa[4]['SSE'] = 100;
        $fsa[0]['ATV'] = 100; $fsa[1]['ATV'] = 100; $fsa[2]['ATV'] = 100; $fsa[3]['ATV'] = 100; $fsa[4]['ATV'] = 100;
        $fsa[0]['TST'] = 100; $fsa[1]['TST'] = 100; $fsa[2]['TST'] = 100; $fsa[3]['TST'] = 100; $fsa[4]['TST'] = 100;
        $fsa[0]['SBT'] = 100; $fsa[1]['SBT'] = 100; $fsa[2]['SBT'] = 100; $fsa[3]['SBT'] = 100; $fsa[4]['SBT'] = 100;
        $fsa[0]['SRE'] = 100; $fsa[1]['SRE'] = 100; $fsa[2]['SRE'] = 100; $fsa[3]['SRE'] = 100; $fsa[4]['SRE'] = 100;
        $fsa[0]['SUM'] = 100; $fsa[1]['SUM'] = 100; $fsa[2]['SUM'] = 100; $fsa[3]['SUM'] = 100; $fsa[4]['SUM'] = 100;
        $fsa[0]['LX']  = 100; $fsa[1]['LX']  = 100; $fsa[2]['LX']  = 100; $fsa[3]['LX']  = 100; $fsa[4]['LX']  = 100;
        $fsa[0]['IMM'] = 100; $fsa[1]['IMM'] = 100; $fsa[2]['IMM'] = 100; $fsa[3]['IMM'] = 100; $fsa[4]['IMM'] = 100;
        $fsa[0]['SES'] = 100; $fsa[1]['SES'] = 100; $fsa[2]['SES'] = 100; $fsa[3]['SES'] = 100; $fsa[4]['SES'] = 100;
        $fsa[0]['CRS'] = 100; $fsa[1]['CRS'] = 100; $fsa[2]['CRS'] = 100; $fsa[3]['CRS'] = 100; $fsa[4]['CRS'] = 100;
        $fsa[0]['CSU'] = 100; $fsa[1]['CSU'] = 100; $fsa[2]['CSU'] = 100; $fsa[3]['CSU'] = 100; $fsa[4]['CSU'] = 100;
        $fsa[0]['MKS'] = 100; $fsa[1]['MKS'] = 100; $fsa[2]['MKS'] = 100; $fsa[3]['MKS'] = 100; $fsa[4]['MKS'] = 100;
        $fsa[0]['DEG'] = 100; $fsa[1]['DEG'] = 100; $fsa[2]['DEG'] = 100; $fsa[3]['DEG'] = 100; $fsa[4]['DEG'] = 100;
        $fsa[0]['FOS'] = 100; $fsa[1]['FOS'] = 100; $fsa[2]['FOS'] = 100; $fsa[3]['FOS'] = 100; $fsa[4]['FOS'] = 100;
        $fsa[0]['SE']  = 100; $fsa[1]['SE']  = 100; $fsa[2]['SE']  = 100; $fsa[3]['SE']  = 100; $fsa[4]['SE']  = 100;
        $fsa[0]['GE']  = 100; $fsa[1]['GE']  = 100; $fsa[2]['GE']  = 100; $fsa[3]['GE']  = 100; $fsa[4]['GE']  = 100;
        $fsa[0]['IEA'] = 100; $fsa[1]['IEA'] = 100; $fsa[2]['IEA'] = 100; $fsa[3]['IEA'] = 100; $fsa[4]['IEA'] = 100;

        $fsa[5]['ISA'] = 100; $fsa[6]['ISA'] = 100; $fsa[7]['ISA'] = 100; $fsa[8]['ISA'] = 100; $fsa[9]['ISA'] = 100;
        $fsa[5]['GS']  = 100; $fsa[6]['GS']  = 100; $fsa[7]['GS']  = 100; $fsa[8]['GS']  = 100; $fsa[9]['GS']  = 100;
        $fsa[5]['ST']  = 100; $fsa[6]['ST']  = 100; $fsa[7]['ST']  = 100; $fsa[8]['ST']  = 100; $fsa[9]['ST']  = 100;
        $fsa[5]['BGN'] = 100; $fsa[6]['BGN'] = 100; $fsa[7]['BGN'] = 100; $fsa[8]['BGN'] = 100; $fsa[9]['BGN'] = 100;
        $fsa[5]['ERP'] = 100; $fsa[6]['ERP'] = 100; $fsa[7]['ERP'] = 100; $fsa[8]['ERP'] = 100; $fsa[9]['ERP'] = 100;
        $fsa[5]['REF'] = 6;   $fsa[6]['REF'] = 6;   $fsa[7]['REF'] = 100; $fsa[8]['REF'] = 100; $fsa[9]['REF'] = 100;
        $fsa[5]['DMG'] = 100; $fsa[6]['DMG'] = 7;   $fsa[7]['DMG'] = 100; $fsa[8]['DMG'] = 100; $fsa[9]['DMG'] = 100;
        $fsa[5]['LUI'] = 100; $fsa[6]['LUI'] = 8;   $fsa[7]['LUI'] = 8;   $fsa[8]['LUI'] = 8;   $fsa[9]['LUI'] = 100;
        $fsa[5]['IND'] = 100; $fsa[6]['IND'] = 9;   $fsa[7]['IND'] = 9;   $fsa[8]['IND'] = 9;   $fsa[9]['IND'] = 9;
        $fsa[5]['DTP'] = 100; $fsa[6]['DTP'] = 10;  $fsa[7]['DTP'] = 10;  $fsa[8]['DTP'] = 10;  $fsa[9]['DTP'] = 10;
        $fsa[5]['RAP'] = 100; $fsa[6]['RAP'] = 11;  $fsa[7]['RAP'] = 11;  $fsa[8]['RAP'] = 11;  $fsa[9]['RAP'] = 11;
        $fsa[5]['PCL'] = 100; $fsa[6]['PCL'] = 12;  $fsa[7]['PCL'] = 12;  $fsa[8]['PCL'] = 12;  $fsa[9]['PCL'] = 12;
        $fsa[5]['NTE'] = 100; $fsa[6]['NTE'] = 13;  $fsa[7]['NTE'] = 13;  $fsa[8]['NTE'] = 13;  $fsa[9]['NTE'] = 13;
        $fsa[5]['N1']  = 100; $fsa[6]['N1']  = 14;  $fsa[7]['N1']  = 14;  $fsa[8]['N1']  = 14;  $fsa[9]['N1']  = 14;
        $fsa[5]['N2']  = 100; $fsa[6]['N2']  = 100; $fsa[7]['N2']  = 100; $fsa[8]['N2']  = 100; $fsa[9]['N2']  = 100;
        $fsa[5]['N3']  = 100; $fsa[6]['N3']  = 100; $fsa[7]['N3']  = 100; $fsa[8]['N3']  = 100; $fsa[9]['N3']  = 100;
        $fsa[5]['N4']  = 100; $fsa[6]['N4']  = 100; $fsa[7]['N4']  = 100; $fsa[8]['N4']  = 100; $fsa[9]['N4']  = 100;
        $fsa[5]['PER'] = 100; $fsa[6]['PER'] = 100; $fsa[7]['PER'] = 100; $fsa[8]['PER'] = 100; $fsa[9]['PER'] = 100;
        $fsa[5]['IN1'] = 100; $fsa[6]['IN1'] = 100; $fsa[7]['IN1'] = 100; $fsa[8]['IN1'] = 100; $fsa[9]['IN1'] = 100;
        $fsa[5]['IN2'] = 100; $fsa[6]['IN2'] = 100; $fsa[7]['IN2'] = 100; $fsa[8]['IN2'] = 100; $fsa[9]['IN2'] = 100;
        $fsa[5]['SST'] = 100; $fsa[6]['SST'] = 100; $fsa[7]['SST'] = 100; $fsa[8]['SST'] = 100; $fsa[9]['SST'] = 100;
        $fsa[5]['SSE'] = 100; $fsa[6]['SSE'] = 100; $fsa[7]['SSE'] = 100; $fsa[8]['SSE'] = 100; $fsa[9]['SSE'] = 100;
        $fsa[5]['ATV'] = 100; $fsa[6]['ATV'] = 100; $fsa[7]['ATV'] = 100; $fsa[8]['ATV'] = 100; $fsa[9]['ATV'] = 100;
        $fsa[5]['TST'] = 100; $fsa[6]['TST'] = 100; $fsa[7]['TST'] = 100; $fsa[8]['TST'] = 100; $fsa[9]['TST'] = 100;
        $fsa[5]['SBT'] = 100; $fsa[6]['SBT'] = 100; $fsa[7]['SBT'] = 100; $fsa[8]['SBT'] = 100; $fsa[9]['SBT'] = 100;
        $fsa[5]['SRE'] = 100; $fsa[6]['SRE'] = 100; $fsa[7]['SRE'] = 100; $fsa[8]['SRE'] = 100; $fsa[9]['SRE'] = 100;
        $fsa[5]['SUM'] = 100; $fsa[6]['SUM'] = 100; $fsa[7]['SUM'] = 100; $fsa[8]['SUM'] = 100; $fsa[9]['SUM'] = 100;
        $fsa[5]['LX']  = 100; $fsa[6]['LX']  = 100; $fsa[7]['LX']  = 100; $fsa[8]['LX']  = 100; $fsa[9]['LX']  = 100;
        $fsa[5]['IMM'] = 100; $fsa[6]['IMM'] = 100; $fsa[7]['IMM'] = 100; $fsa[8]['IMM'] = 100; $fsa[9]['IMM'] = 100;
        $fsa[5]['SES'] = 100; $fsa[6]['SES'] = 100; $fsa[7]['SES'] = 100; $fsa[8]['SES'] = 100; $fsa[9]['SES'] = 100;
        $fsa[5]['CRS'] = 100; $fsa[6]['CRS'] = 100; $fsa[7]['CRS'] = 100; $fsa[8]['CRS'] = 100; $fsa[9]['CRS'] = 100;
        $fsa[5]['CSU'] = 100; $fsa[6]['CSU'] = 100; $fsa[7]['CSU'] = 100; $fsa[8]['CSU'] = 100; $fsa[9]['CSU'] = 100;
        $fsa[5]['MKS'] = 100; $fsa[6]['MKS'] = 100; $fsa[7]['MKS'] = 100; $fsa[8]['MKS'] = 100; $fsa[9]['MKS'] = 100;
        $fsa[5]['DEG'] = 100; $fsa[6]['DEG'] = 100; $fsa[7]['DEG'] = 100; $fsa[8]['DEG'] = 100; $fsa[9]['DEG'] = 100;
        $fsa[5]['FOS'] = 100; $fsa[6]['FOS'] = 100; $fsa[7]['FOS'] = 100; $fsa[8]['FOS'] = 100; $fsa[9]['FOS'] = 100;
        $fsa[5]['SE']  = 100; $fsa[6]['SE']  = 100; $fsa[7]['SE']  = 100; $fsa[8]['SE']  = 100; $fsa[9]['SE']  = 100;
        $fsa[5]['GE']  = 100; $fsa[6]['GE']  = 100; $fsa[7]['GE']  = 100; $fsa[8]['GE']  = 100; $fsa[9]['GE']  = 100;
        $fsa[5]['IEA'] = 100; $fsa[6]['IEA'] = 100; $fsa[7]['IEA'] = 100; $fsa[8]['IEA'] = 100; $fsa[9]['IEA'] = 100;

        $fsa[10]['ISA'] = 100; $fsa[11]['ISA'] = 100; $fsa[12]['ISA'] = 100; $fsa[13]['ISA'] = 100; $fsa[14]['ISA'] = 100;
        $fsa[10]['GS']  = 100; $fsa[11]['GS']  = 100; $fsa[12]['GS']  = 100; $fsa[13]['GS']  = 100; $fsa[14]['GS']  = 100;
        $fsa[10]['ST']  = 100; $fsa[11]['ST']  = 100; $fsa[12]['ST']  = 100; $fsa[13]['ST']  = 100; $fsa[14]['ST']  = 100;
        $fsa[10]['BGN'] = 100; $fsa[11]['BGN'] = 100; $fsa[12]['BGN'] = 100; $fsa[13]['BGN'] = 100; $fsa[14]['BGN'] = 100;
        $fsa[10]['ERP'] = 100; $fsa[11]['ERP'] = 100; $fsa[12]['ERP'] = 100; $fsa[13]['ERP'] = 100; $fsa[14]['ERP'] = 100;
        $fsa[10]['REF'] = 100; $fsa[11]['REF'] = 100; $fsa[12]['REF'] = 100; $fsa[13]['REF'] = 100; $fsa[14]['REF'] = 100;
        $fsa[10]['DMG'] = 100; $fsa[11]['DMG'] = 100; $fsa[12]['DMG'] = 100; $fsa[13]['DMG'] = 100; $fsa[14]['DMG'] = 100;
        $fsa[10]['LUI'] = 100; $fsa[11]['LUI'] = 100; $fsa[12]['LUI'] = 100; $fsa[13]['LUI'] = 100; $fsa[14]['LUI'] = 100;
        $fsa[10]['IND'] = 100; $fsa[11]['IND'] = 100; $fsa[12]['IND'] = 100; $fsa[13]['IND'] = 100; $fsa[14]['IND'] = 100;
        $fsa[10]['DTP'] = 100; $fsa[11]['DTP'] = 100; $fsa[12]['DTP'] = 100; $fsa[13]['DTP'] = 100; $fsa[14]['DTP'] = 100;
        $fsa[10]['RAP'] = 11;  $fsa[11]['RAP'] = 11;  $fsa[12]['RAP'] = 100; $fsa[13]['RAP'] = 100; $fsa[14]['RAP'] = 100;
        $fsa[10]['PCL'] = 12;  $fsa[11]['PCL'] = 12;  $fsa[12]['PCL'] = 12;  $fsa[13]['PCL'] = 100; $fsa[14]['PCL'] = 100;
        $fsa[10]['NTE'] = 13;  $fsa[11]['NTE'] = 13;  $fsa[12]['NTE'] = 13;  $fsa[13]['NTE'] = 13;  $fsa[14]['NTE'] = 100;
        $fsa[10]['N1']  = 14;  $fsa[11]['N1']  = 14;  $fsa[12]['N1']  = 14;  $fsa[13]['N1']  = 14;  $fsa[14]['N1']  = 14;
        $fsa[10]['N2']  = 100; $fsa[11]['N2']  = 100; $fsa[12]['N2']  = 100; $fsa[13]['N2']  = 100; $fsa[14]['N2']  = 15;
        $fsa[10]['N3']  = 100; $fsa[11]['N3']  = 100; $fsa[12]['N3']  = 100; $fsa[13]['N3']  = 100; $fsa[14]['N3']  = 16;
        $fsa[10]['N4']  = 100; $fsa[11]['N4']  = 100; $fsa[12]['N4']  = 100; $fsa[13]['N4']  = 100; $fsa[14]['N4']  = 17;
        $fsa[10]['PER'] = 100; $fsa[11]['PER'] = 100; $fsa[12]['PER'] = 100; $fsa[13]['PER'] = 100; $fsa[14]['PER'] = 18;
        $fsa[10]['IN1'] = 100; $fsa[11]['IN1'] = 100; $fsa[12]['IN1'] = 100; $fsa[13]['IN1'] = 100; $fsa[14]['IN1'] = 19;
        $fsa[10]['IN2'] = 100; $fsa[11]['IN2'] = 100; $fsa[12]['IN2'] = 100; $fsa[13]['IN2'] = 100; $fsa[14]['IN2'] = 100;
        $fsa[10]['SST'] = 100; $fsa[11]['SST'] = 100; $fsa[12]['SST'] = 100; $fsa[13]['SST'] = 100; $fsa[14]['SST'] = 100;
        $fsa[10]['SSE'] = 100; $fsa[11]['SSE'] = 100; $fsa[12]['SSE'] = 100; $fsa[13]['SSE'] = 100; $fsa[14]['SSE'] = 100;
        $fsa[10]['ATV'] = 100; $fsa[11]['ATV'] = 100; $fsa[12]['ATV'] = 100; $fsa[13]['ATV'] = 100; $fsa[14]['ATV'] = 100;
        $fsa[10]['TST'] = 100; $fsa[11]['TST'] = 100; $fsa[12]['TST'] = 100; $fsa[13]['TST'] = 100; $fsa[14]['TST'] = 100;
        $fsa[10]['SBT'] = 100; $fsa[11]['SBT'] = 100; $fsa[12]['SBT'] = 100; $fsa[13]['SBT'] = 100; $fsa[14]['SBT'] = 100;
        $fsa[10]['SRE'] = 100; $fsa[11]['SRE'] = 100; $fsa[12]['SRE'] = 100; $fsa[13]['SRE'] = 100; $fsa[14]['SRE'] = 100;
        $fsa[10]['SUM'] = 100; $fsa[11]['SUM'] = 100; $fsa[12]['SUM'] = 100; $fsa[13]['SUM'] = 100; $fsa[14]['SUM'] = 100;
        $fsa[10]['LX']  = 100; $fsa[11]['LX']  = 100; $fsa[12]['LX']  = 100; $fsa[13]['LX']  = 100; $fsa[14]['LX']  = 100;
        $fsa[10]['IMM'] = 100; $fsa[11]['IMM'] = 100; $fsa[12]['IMM'] = 100; $fsa[13]['IMM'] = 100; $fsa[14]['IMM'] = 100;
        $fsa[10]['SES'] = 100; $fsa[11]['SES'] = 100; $fsa[12]['SES'] = 100; $fsa[13]['SES'] = 100; $fsa[14]['SES'] = 100;
        $fsa[10]['CRS'] = 100; $fsa[11]['CRS'] = 100; $fsa[12]['CRS'] = 100; $fsa[13]['CRS'] = 100; $fsa[14]['CRS'] = 100;
        $fsa[10]['CSU'] = 100; $fsa[11]['CSU'] = 100; $fsa[12]['CSU'] = 100; $fsa[13]['CSU'] = 100; $fsa[14]['CSU'] = 100;
        $fsa[10]['MKS'] = 100; $fsa[11]['MKS'] = 100; $fsa[12]['MKS'] = 100; $fsa[13]['MKS'] = 100; $fsa[14]['MKS'] = 100;
        $fsa[10]['DEG'] = 100; $fsa[11]['DEG'] = 100; $fsa[12]['DEG'] = 100; $fsa[13]['DEG'] = 100; $fsa[14]['DEG'] = 100;
        $fsa[10]['FOS'] = 100; $fsa[11]['FOS'] = 100; $fsa[12]['FOS'] = 100; $fsa[13]['FOS'] = 100; $fsa[14]['FOS'] = 100;
        $fsa[10]['SE']  = 100; $fsa[11]['SE']  = 100; $fsa[12]['SE']  = 100; $fsa[13]['SE']  = 100; $fsa[14]['SE']  = 100;
        $fsa[10]['GE']  = 100; $fsa[11]['GE']  = 100; $fsa[12]['GE']  = 100; $fsa[13]['GE']  = 100; $fsa[14]['GE']  = 100;
        $fsa[10]['IEA'] = 100; $fsa[11]['IEA'] = 100; $fsa[12]['IEA'] = 100; $fsa[13]['IEA'] = 100; $fsa[14]['IEA'] = 100;

        $fsa[15]['ISA'] = 100; $fsa[16]['ISA'] = 100; $fsa[17]['ISA'] = 100; $fsa[18]['ISA'] = 100; $fsa[19]['ISA'] = 100;
        $fsa[15]['GS']  = 100; $fsa[16]['GS']  = 100; $fsa[17]['GS']  = 100; $fsa[18]['GS']  = 100; $fsa[19]['GS']  = 100;
        $fsa[15]['ST']  = 100; $fsa[16]['ST']  = 100; $fsa[17]['ST']  = 100; $fsa[18]['ST']  = 100; $fsa[19]['ST']  = 100;
        $fsa[15]['BGN'] = 100; $fsa[16]['BGN'] = 100; $fsa[17]['BGN'] = 100; $fsa[18]['BGN'] = 100; $fsa[19]['BGN'] = 100;
        $fsa[15]['ERP'] = 100; $fsa[16]['ERP'] = 100; $fsa[17]['ERP'] = 100; $fsa[18]['ERP'] = 100; $fsa[19]['ERP'] = 100;
        $fsa[15]['REF'] = 100; $fsa[16]['REF'] = 100; $fsa[17]['REF'] = 100; $fsa[18]['REF'] = 100; $fsa[19]['REF'] = 100;
        $fsa[15]['DMG'] = 100; $fsa[16]['DMG'] = 100; $fsa[17]['DMG'] = 100; $fsa[18]['DMG'] = 100; $fsa[19]['DMG'] = 100;
        $fsa[15]['LUI'] = 100; $fsa[16]['LUI'] = 100; $fsa[17]['LUI'] = 100; $fsa[18]['LUI'] = 100; $fsa[19]['LUI'] = 100;
        $fsa[15]['IND'] = 100; $fsa[16]['IND'] = 100; $fsa[17]['IND'] = 100; $fsa[18]['IND'] = 100; $fsa[19]['IND'] = 100;
        $fsa[15]['DTP'] = 100; $fsa[16]['DTP'] = 100; $fsa[17]['DTP'] = 100; $fsa[18]['DTP'] = 100; $fsa[19]['DTP'] = 100;
        $fsa[15]['RAP'] = 100; $fsa[16]['RAP'] = 100; $fsa[17]['RAP'] = 100; $fsa[18]['RAP'] = 100; $fsa[19]['RAP'] = 100;
        $fsa[15]['PCL'] = 100; $fsa[16]['PCL'] = 100; $fsa[17]['PCL'] = 100; $fsa[18]['PCL'] = 100; $fsa[19]['PCL'] = 100;
        $fsa[15]['NTE'] = 100; $fsa[16]['NTE'] = 100; $fsa[17]['NTE'] = 100; $fsa[18]['NTE'] = 100; $fsa[19]['NTE'] = 100;
        $fsa[15]['N1']  = 14;  $fsa[16]['N1']  = 14;  $fsa[17]['N1']  = 14;  $fsa[18]['N1']  = 14;  $fsa[19]['N1']  = 100;
        $fsa[15]['N2']  = 100; $fsa[16]['N2']  = 100; $fsa[17]['N2']  = 100; $fsa[18]['N2']  = 100; $fsa[19]['N2']  = 100;
        $fsa[15]['N3']  = 16;  $fsa[16]['N3']  = 100; $fsa[17]['N3']  = 100; $fsa[18]['N3']  = 100; $fsa[19]['N3']  = 100;
        $fsa[15]['N4']  = 17;  $fsa[16]['N4']  = 17;  $fsa[17]['N4']  = 100; $fsa[18]['N4']  = 100; $fsa[19]['N4']  = 100;
        $fsa[15]['PER'] = 18;  $fsa[16]['PER'] = 18;  $fsa[17]['PER'] = 18;  $fsa[18]['PER'] = 100; $fsa[19]['PER'] = 100;
        $fsa[15]['IN1'] = 19;  $fsa[16]['IN1'] = 19;  $fsa[17]['IN1'] = 19;  $fsa[18]['IN1'] = 19;  $fsa[19]['IN1'] = 100;
        $fsa[15]['IN2'] = 100; $fsa[16]['IN2'] = 100; $fsa[17]['IN2'] = 100; $fsa[18]['IN2'] = 100; $fsa[19]['IN2'] = 20;
        $fsa[15]['SST'] = 100; $fsa[16]['SST'] = 100; $fsa[17]['SST'] = 100; $fsa[18]['SST'] = 100; $fsa[19]['SST'] = 100;
        $fsa[15]['SSE'] = 100; $fsa[16]['SSE'] = 100; $fsa[17]['SSE'] = 100; $fsa[18]['SSE'] = 100; $fsa[19]['SSE'] = 100;
        $fsa[15]['ATV'] = 100; $fsa[16]['ATV'] = 100; $fsa[17]['ATV'] = 100; $fsa[18]['ATV'] = 100; $fsa[19]['ATV'] = 100;
        $fsa[15]['TST'] = 100; $fsa[16]['TST'] = 100; $fsa[17]['TST'] = 100; $fsa[18]['TST'] = 100; $fsa[19]['TST'] = 100;
        $fsa[15]['SBT'] = 100; $fsa[16]['SBT'] = 100; $fsa[17]['SBT'] = 100; $fsa[18]['SBT'] = 100; $fsa[19]['SBT'] = 100;
        $fsa[15]['SRE'] = 100; $fsa[16]['SRE'] = 100; $fsa[17]['SRE'] = 100; $fsa[18]['SRE'] = 100; $fsa[19]['SRE'] = 100;
        $fsa[15]['SUM'] = 100; $fsa[16]['SUM'] = 100; $fsa[17]['SUM'] = 100; $fsa[18]['SUM'] = 100; $fsa[19]['SUM'] = 100;
        $fsa[15]['LX']  = 100; $fsa[16]['LX']  = 100; $fsa[17]['LX']  = 100; $fsa[18]['LX']  = 100; $fsa[19]['LX']  = 100;
        $fsa[15]['IMM'] = 100; $fsa[16]['IMM'] = 100; $fsa[17]['IMM'] = 100; $fsa[18]['IMM'] = 100; $fsa[19]['IMM'] = 100;
        $fsa[15]['SES'] = 100; $fsa[16]['SES'] = 100; $fsa[17]['SES'] = 100; $fsa[18]['SES'] = 100; $fsa[19]['SES'] = 100;
        $fsa[15]['CRS'] = 100; $fsa[16]['CRS'] = 100; $fsa[17]['CRS'] = 100; $fsa[18]['CRS'] = 100; $fsa[19]['CRS'] = 100;
        $fsa[15]['CSU'] = 100; $fsa[16]['CSU'] = 100; $fsa[17]['CSU'] = 100; $fsa[18]['CSU'] = 100; $fsa[19]['CSU'] = 100;
        $fsa[15]['MKS'] = 100; $fsa[16]['MKS'] = 100; $fsa[17]['MKS'] = 100; $fsa[18]['MKS'] = 100; $fsa[19]['MKS'] = 100;
        $fsa[15]['DEG'] = 100; $fsa[16]['DEG'] = 100; $fsa[17]['DEG'] = 100; $fsa[18]['DEG'] = 100; $fsa[19]['DEG'] = 100;
        $fsa[15]['FOS'] = 100; $fsa[16]['FOS'] = 100; $fsa[17]['FOS'] = 100; $fsa[18]['FOS'] = 100; $fsa[19]['FOS'] = 100;
        $fsa[15]['SE']  = 100; $fsa[16]['SE']  = 100; $fsa[17]['SE']  = 100; $fsa[18]['SE']  = 100; $fsa[19]['SE']  = 100;
        $fsa[15]['GE']  = 100; $fsa[16]['GE']  = 100; $fsa[17]['GE']  = 100; $fsa[18]['GE']  = 100; $fsa[19]['GE']  = 100;
        $fsa[15]['IEA'] = 100; $fsa[16]['IEA'] = 100; $fsa[17]['IEA'] = 100; $fsa[18]['IEA'] = 100; $fsa[19]['IEA'] = 100;

        $fsa[20]['ISA'] = 100; $fsa[21]['ISA'] = 100; $fsa[22]['ISA'] = 100; $fsa[23]['ISA'] = 100; $fsa[24]['ISA'] = 100;
        $fsa[20]['GS']  = 100; $fsa[21]['GS']  = 100; $fsa[22]['GS']  = 100; $fsa[23]['GS']  = 100; $fsa[24]['GS']  = 100;
        $fsa[20]['ST']  = 100; $fsa[21]['ST']  = 100; $fsa[22]['ST']  = 100; $fsa[23]['ST']  = 100; $fsa[24]['ST']  = 100;
        $fsa[20]['BGN'] = 100; $fsa[21]['BGN'] = 100; $fsa[22]['BGN'] = 100; $fsa[23]['BGN'] = 100; $fsa[24]['BGN'] = 100;
        $fsa[20]['ERP'] = 100; $fsa[21]['ERP'] = 100; $fsa[22]['ERP'] = 100; $fsa[23]['ERP'] = 100; $fsa[24]['ERP'] = 100;
        $fsa[20]['REF'] = 100; $fsa[21]['REF'] = 100; $fsa[22]['REF'] = 100; $fsa[23]['REF'] = 100; $fsa[24]['REF'] = 100;
        $fsa[20]['DMG'] = 100; $fsa[21]['DMG'] = 100; $fsa[22]['DMG'] = 100; $fsa[23]['DMG'] = 100; $fsa[24]['DMG'] = 100;
        $fsa[20]['LUI'] = 100; $fsa[21]['LUI'] = 100; $fsa[22]['LUI'] = 100; $fsa[23]['LUI'] = 100; $fsa[24]['LUI'] = 100;
        $fsa[20]['IND'] = 100; $fsa[21]['IND'] = 100; $fsa[22]['IND'] = 100; $fsa[23]['IND'] = 100; $fsa[24]['IND'] = 100;
        $fsa[20]['DTP'] = 100; $fsa[21]['DTP'] = 100; $fsa[22]['DTP'] = 100; $fsa[23]['DTP'] = 100; $fsa[24]['DTP'] = 100;
        $fsa[20]['RAP'] = 100; $fsa[21]['RAP'] = 100; $fsa[22]['RAP'] = 100; $fsa[23]['RAP'] = 100; $fsa[24]['RAP'] = 100;
        $fsa[20]['PCL'] = 100; $fsa[21]['PCL'] = 100; $fsa[22]['PCL'] = 100; $fsa[23]['PCL'] = 100; $fsa[24]['PCL'] = 100;
        $fsa[20]['NTE'] = 23;  $fsa[21]['NTE'] = 23;  $fsa[22]['NTE'] = 23;  $fsa[23]['NTE'] = 100; $fsa[24]['NTE'] = 100;
        $fsa[20]['N1']  = 100; $fsa[21]['N1']  = 100; $fsa[22]['N1']  = 100; $fsa[23]['N1']  = 100; $fsa[24]['N1']  = 31;
        $fsa[20]['N2']  = 100; $fsa[21]['N2']  = 100; $fsa[22]['N2']  = 100; $fsa[23]['N2']  = 100; $fsa[24]['N2']  = 100;
        $fsa[20]['N3']  = 21;  $fsa[21]['N3']  = 21;  $fsa[22]['N3']  = 100; $fsa[23]['N3']  = 100; $fsa[24]['N3']  = 32;
        $fsa[20]['N4']  = 100; $fsa[21]['N4']  = 29;  $fsa[22]['N4']  = 100; $fsa[23]['N4']  = 100; $fsa[24]['N4']  = 33;
        $fsa[20]['PER'] = 22;  $fsa[21]['PER'] = 22;  $fsa[22]['PER'] = 22;  $fsa[23]['PER'] = 100; $fsa[24]['PER'] = 100;
        $fsa[20]['IN1'] = 19;  $fsa[21]['IN1'] = 19;  $fsa[22]['IN1'] = 19;  $fsa[23]['IN1'] = 19;  $fsa[24]['IN1'] = 100;
        $fsa[20]['IN2'] = 20;  $fsa[21]['IN2'] = 100; $fsa[22]['IN2'] = 100; $fsa[23]['IN2'] = 100; $fsa[24]['IN2'] = 100;
        $fsa[20]['SST'] = 24;  $fsa[21]['SST'] = 24;  $fsa[22]['SST'] = 24;  $fsa[23]['SST'] = 24;  $fsa[24]['SST'] = 24;
        $fsa[20]['SSE'] = 100; $fsa[21]['SSE'] = 100; $fsa[22]['SSE'] = 100; $fsa[23]['SSE'] = 100; $fsa[24]['SSE'] = 30;
        $fsa[20]['ATV'] = 25;  $fsa[21]['ATV'] = 25;  $fsa[22]['ATV'] = 25;  $fsa[23]['ATV'] = 25;  $fsa[24]['ATV'] = 25;
        $fsa[20]['TST'] = 26;  $fsa[21]['TST'] = 26;  $fsa[22]['TST'] = 26;  $fsa[23]['TST'] = 26;  $fsa[24]['TST'] = 26;
        $fsa[20]['SBT'] = 100; $fsa[21]['SBT'] = 100; $fsa[22]['SBT'] = 100; $fsa[23]['SBT'] = 100; $fsa[24]['SBT'] = 100;
        $fsa[20]['SRE'] = 100; $fsa[21]['SRE'] = 100; $fsa[22]['SRE'] = 100; $fsa[23]['SRE'] = 100; $fsa[24]['SRE'] = 100;
        $fsa[20]['SUM'] = 27;  $fsa[21]['SUM'] = 27;  $fsa[22]['SUM'] = 27;  $fsa[23]['SUM'] = 27;  $fsa[24]['SUM'] = 27;
        $fsa[20]['LX']  = 28;  $fsa[21]['LX']  = 28;  $fsa[22]['LX']  = 28;  $fsa[23]['LX']  = 28;  $fsa[24]['LX']  = 28;
        $fsa[20]['IMM'] = 100; $fsa[21]['IMM'] = 100; $fsa[22]['IMM'] = 100; $fsa[23]['IMM'] = 100; $fsa[24]['IMM'] = 100;
        $fsa[20]['SES'] = 100; $fsa[21]['SES'] = 100; $fsa[22]['SES'] = 100; $fsa[23]['SES'] = 100; $fsa[24]['SES'] = 100;
        $fsa[20]['CRS'] = 100; $fsa[21]['CRS'] = 100; $fsa[22]['CRS'] = 100; $fsa[23]['CRS'] = 100; $fsa[24]['CRS'] = 100;
        $fsa[20]['CSU'] = 100; $fsa[21]['CSU'] = 100; $fsa[22]['CSU'] = 100; $fsa[23]['CSU'] = 100; $fsa[24]['CSU'] = 100;
        $fsa[20]['MKS'] = 100; $fsa[21]['MKS'] = 100; $fsa[22]['MKS'] = 100; $fsa[23]['MKS'] = 100; $fsa[24]['MKS'] = 100;
        $fsa[20]['DEG'] = 100; $fsa[21]['DEG'] = 100; $fsa[22]['DEG'] = 100; $fsa[23]['DEG'] = 100; $fsa[24]['DEG'] = 100;
        $fsa[20]['FOS'] = 100; $fsa[21]['FOS'] = 100; $fsa[22]['FOS'] = 100; $fsa[23]['FOS'] = 100; $fsa[24]['FOS'] = 100;
        $fsa[20]['SE']  = 100; $fsa[21]['SE']  = 100; $fsa[22]['SE']  = 100; $fsa[23]['SE']  = 100; $fsa[24]['SE']  = 100;
        $fsa[20]['GE']  = 100; $fsa[21]['GE']  = 100; $fsa[22]['GE']  = 100; $fsa[23]['GE']  = 100; $fsa[24]['GE']  = 100;
        $fsa[20]['IEA'] = 100; $fsa[21]['IEA'] = 100; $fsa[22]['IEA'] = 100; $fsa[23]['IEA'] = 100; $fsa[24]['IEA'] = 100;

        $fsa[25]['ISA'] = 100; $fsa[26]['ISA'] = 100; $fsa[27]['ISA'] = 100; $fsa[28]['ISA'] = 100; $fsa[29]['ISA'] = 100;
        $fsa[25]['GS']  = 100; $fsa[26]['GS']  = 100; $fsa[27]['GS']  = 100; $fsa[28]['GS']  = 100; $fsa[29]['GS']  = 100;
        $fsa[25]['ST']  = 100; $fsa[26]['ST']  = 100; $fsa[27]['ST']  = 100; $fsa[28]['ST']  = 100; $fsa[29]['ST']  = 100;
        $fsa[25]['BGN'] = 100; $fsa[26]['BGN'] = 100; $fsa[27]['BGN'] = 100; $fsa[28]['BGN'] = 100; $fsa[29]['BGN'] = 100;
        $fsa[25]['ERP'] = 100; $fsa[26]['ERP'] = 100; $fsa[27]['ERP'] = 100; $fsa[28]['ERP'] = 100; $fsa[29]['ERP'] = 100;
        $fsa[25]['REF'] = 100; $fsa[26]['REF'] = 100; $fsa[27]['REF'] = 100; $fsa[28]['REF'] = 100; $fsa[29]['REF'] = 100;
        $fsa[25]['DMG'] = 100; $fsa[26]['DMG'] = 100; $fsa[27]['DMG'] = 100; $fsa[28]['DMG'] = 100; $fsa[29]['DMG'] = 100;
        $fsa[25]['LUI'] = 100; $fsa[26]['LUI'] = 100; $fsa[27]['LUI'] = 100; $fsa[28]['LUI'] = 100; $fsa[29]['LUI'] = 100;
        $fsa[25]['IND'] = 100; $fsa[26]['IND'] = 100; $fsa[27]['IND'] = 100; $fsa[28]['IND'] = 100; $fsa[29]['IND'] = 100;
        $fsa[25]['DTP'] = 34;  $fsa[26]['DTP'] = 100; $fsa[27]['DTP'] = 100; $fsa[28]['DTP'] = 100; $fsa[29]['DTP'] = 100;
        $fsa[25]['RAP'] = 100; $fsa[26]['RAP'] = 100; $fsa[27]['RAP'] = 100; $fsa[28]['RAP'] = 100; $fsa[29]['RAP'] = 100;
        $fsa[25]['PCL'] = 100; $fsa[26]['PCL'] = 100; $fsa[27]['PCL'] = 100; $fsa[28]['PCL'] = 100; $fsa[29]['PCL'] = 100;
        $fsa[25]['NTE'] = 100; $fsa[26]['NTE'] = 100; $fsa[27]['NTE'] = 36;  $fsa[28]['NTE'] = 100; $fsa[29]['NTE'] = 23;
        $fsa[25]['N1']  = 100; $fsa[26]['N1']  = 100; $fsa[27]['N1']  = 100; $fsa[28]['N1']  = 100; $fsa[29]['N1']  = 100;
        $fsa[25]['N2']  = 100; $fsa[26]['N2']  = 100; $fsa[27]['N2']  = 100; $fsa[28]['N2']  = 100; $fsa[29]['N2']  = 100;
        $fsa[25]['N3']  = 100; $fsa[26]['N3']  = 100; $fsa[27]['N3']  = 100; $fsa[28]['N3']  = 100; $fsa[29]['N3']  = 100;
        $fsa[25]['N4']  = 100; $fsa[26]['N4']  = 100; $fsa[27]['N4']  = 100; $fsa[28]['N4']  = 100; $fsa[29]['N4']  = 100;
        $fsa[25]['PER'] = 100; $fsa[26]['PER'] = 100; $fsa[27]['PER'] = 100; $fsa[28]['PER'] = 100; $fsa[29]['PER'] = 22;
        $fsa[25]['IN1'] = 100; $fsa[26]['IN1'] = 100; $fsa[27]['IN1'] = 100; $fsa[28]['IN1'] = 100; $fsa[29]['IN1'] = 19;
        $fsa[25]['IN2'] = 100; $fsa[26]['IN2'] = 100; $fsa[27]['IN2'] = 100; $fsa[28]['IN2'] = 100; $fsa[29]['IN2'] = 100;
        $fsa[25]['SST'] = 100; $fsa[26]['SST'] = 100; $fsa[27]['SST'] = 100; $fsa[28]['SST'] = 100; $fsa[29]['SST'] = 24;
        $fsa[25]['SSE'] = 100; $fsa[26]['SSE'] = 100; $fsa[27]['SSE'] = 100; $fsa[28]['SSE'] = 100; $fsa[29]['SSE'] = 100;
        $fsa[25]['ATV'] = 25;  $fsa[26]['ATV'] = 100; $fsa[27]['ATV'] = 100; $fsa[28]['ATV'] = 100; $fsa[29]['ATV'] = 25;
        $fsa[25]['TST'] = 26;  $fsa[26]['TST'] = 26;  $fsa[27]['TST'] = 100; $fsa[28]['TST'] = 100; $fsa[29]['TST'] = 26;
        $fsa[25]['SBT'] = 100; $fsa[26]['SBT'] = 35;  $fsa[27]['SBT'] = 100; $fsa[28]['SBT'] = 100; $fsa[29]['SBT'] = 100;
        $fsa[25]['SRE'] = 100; $fsa[26]['SRE'] = 63;  $fsa[27]['SRE'] = 100; $fsa[28]['SRE'] = 100; $fsa[29]['SRE'] = 100;
        $fsa[25]['SUM'] = 27;  $fsa[26]['SUM'] = 27;  $fsa[27]['SUM'] = 27;  $fsa[28]['SUM'] = 100; $fsa[29]['SUM'] = 27;
        $fsa[25]['LX']  = 28;  $fsa[26]['LX']  = 28;  $fsa[27]['LX']  = 28;  $fsa[28]['LX']  = 100; $fsa[29]['LX']  = 28;
        $fsa[25]['IMM'] = 100; $fsa[26]['IMM'] = 100; $fsa[27]['IMM'] = 100; $fsa[28]['IMM'] = 37;  $fsa[29]['IMM'] = 100;
        $fsa[25]['SES'] = 100; $fsa[26]['SES'] = 100; $fsa[27]['SES'] = 100; $fsa[28]['SES'] = 38;  $fsa[29]['SES'] = 100;
        $fsa[25]['CRS'] = 100; $fsa[26]['CRS'] = 100; $fsa[27]['CRS'] = 100; $fsa[28]['CRS'] = 100; $fsa[29]['CRS'] = 100;
        $fsa[25]['CSU'] = 100; $fsa[26]['CSU'] = 100; $fsa[27]['CSU'] = 100; $fsa[28]['CSU'] = 100; $fsa[29]['CSU'] = 100;
        $fsa[25]['MKS'] = 100; $fsa[26]['MKS'] = 100; $fsa[27]['MKS'] = 100; $fsa[28]['MKS'] = 100; $fsa[29]['MKS'] = 100;
        $fsa[25]['DEG'] = 100; $fsa[26]['DEG'] = 100; $fsa[27]['DEG'] = 100; $fsa[28]['DEG'] = 100; $fsa[29]['DEG'] = 100;
        $fsa[25]['FOS'] = 100; $fsa[26]['FOS'] = 100; $fsa[27]['FOS'] = 100; $fsa[28]['FOS'] = 100; $fsa[29]['FOS'] = 100;
        $fsa[25]['SE']  = 100; $fsa[26]['SE']  = 100; $fsa[27]['SE']  = 100; $fsa[28]['SE']  = 39;  $fsa[29]['SE']  = 100;
        $fsa[25]['GE']  = 100; $fsa[26]['GE']  = 100; $fsa[27]['GE']  = 100; $fsa[28]['GE']  = 100; $fsa[29]['GE']  = 100;
        $fsa[25]['IEA'] = 100; $fsa[26]['IEA'] = 100; $fsa[27]['IEA'] = 100; $fsa[28]['IEA'] = 100; $fsa[29]['IEA'] = 100;

        $fsa[30]['ISA'] = 100; $fsa[31]['ISA'] = 100; $fsa[32]['ISA'] = 100; $fsa[33]['ISA'] = 100; $fsa[34]['ISA'] = 100;
        $fsa[30]['GS']  = 100; $fsa[31]['GS']  = 100; $fsa[32]['GS']  = 100; $fsa[33]['GS']  = 100; $fsa[34]['GS']  = 100;
        $fsa[30]['ST']  = 100; $fsa[31]['ST']  = 100; $fsa[32]['ST']  = 100; $fsa[33]['ST']  = 100; $fsa[34]['ST']  = 100;
        $fsa[30]['BGN'] = 100; $fsa[31]['BGN'] = 100; $fsa[32]['BGN'] = 100; $fsa[33]['BGN'] = 100; $fsa[34]['BGN'] = 100;
        $fsa[30]['ERP'] = 100; $fsa[31]['ERP'] = 100; $fsa[32]['ERP'] = 100; $fsa[33]['ERP'] = 100; $fsa[34]['ERP'] = 100;
        $fsa[30]['REF'] = 100; $fsa[31]['REF'] = 100; $fsa[32]['REF'] = 100; $fsa[33]['REF'] = 100; $fsa[34]['REF'] = 100;
        $fsa[30]['DMG'] = 100; $fsa[31]['DMG'] = 100; $fsa[32]['DMG'] = 100; $fsa[33]['DMG'] = 100; $fsa[34]['DMG'] = 100;
        $fsa[30]['LUI'] = 100; $fsa[31]['LUI'] = 100; $fsa[32]['LUI'] = 100; $fsa[33]['LUI'] = 100; $fsa[34]['LUI'] = 100;
        $fsa[30]['IND'] = 100; $fsa[31]['IND'] = 100; $fsa[32]['IND'] = 100; $fsa[33]['IND'] = 100; $fsa[34]['IND'] = 100;
        $fsa[30]['DTP'] = 100; $fsa[31]['DTP'] = 100; $fsa[32]['DTP'] = 100; $fsa[33]['DTP'] = 100; $fsa[34]['DTP'] = 100;
        $fsa[30]['RAP'] = 100; $fsa[31]['RAP'] = 100; $fsa[32]['RAP'] = 100; $fsa[33]['RAP'] = 100; $fsa[34]['RAP'] = 100;
        $fsa[30]['PCL'] = 100; $fsa[31]['PCL'] = 100; $fsa[32]['PCL'] = 100; $fsa[33]['PCL'] = 100; $fsa[34]['PCL'] = 100;
        $fsa[30]['NTE'] = 100; $fsa[31]['NTE'] = 100; $fsa[32]['NTE'] = 100; $fsa[33]['NTE'] = 100; $fsa[34]['NTE'] = 100;
        $fsa[30]['N1']  = 31;  $fsa[31]['N1']  = 100; $fsa[32]['N1']  = 100; $fsa[33]['N1']  = 100; $fsa[34]['N1']  = 100;
        $fsa[30]['N2']  = 100; $fsa[31]['N2']  = 100; $fsa[32]['N2']  = 100; $fsa[33]['N2']  = 100; $fsa[34]['N2']  = 100;
        $fsa[30]['N3']  = 32;  $fsa[31]['N3']  = 32;  $fsa[32]['N3']  = 100; $fsa[33]['N3']  = 100; $fsa[34]['N3']  = 100;
        $fsa[30]['N4']  = 33;  $fsa[31]['N4']  = 33;  $fsa[32]['N4']  = 33;  $fsa[33]['N4']  = 100; $fsa[34]['N4']  = 100;
        $fsa[30]['PER'] = 100; $fsa[31]['PER'] = 100; $fsa[32]['PER'] = 100; $fsa[33]['PER'] = 100; $fsa[34]['PER'] = 100;
        $fsa[30]['IN1'] = 100; $fsa[31]['IN1'] = 100; $fsa[32]['IN1'] = 100; $fsa[33]['IN1'] = 100; $fsa[34]['IN1'] = 100;
        $fsa[30]['IN2'] = 100; $fsa[31]['IN2'] = 100; $fsa[32]['IN2'] = 100; $fsa[33]['IN2'] = 100; $fsa[34]['IN2'] = 100;
        $fsa[30]['SST'] = 24;  $fsa[31]['SST'] = 24;  $fsa[32]['SST'] = 24;  $fsa[33]['SST'] = 24;  $fsa[34]['SST'] = 100;
        $fsa[30]['SSE'] = 30;  $fsa[31]['SSE'] = 100; $fsa[32]['SSE'] = 100; $fsa[33]['SSE'] = 100; $fsa[34]['SSE'] = 100;
        $fsa[30]['ATV'] = 25;  $fsa[31]['ATV'] = 25;  $fsa[32]['ATV'] = 25;  $fsa[33]['ATV'] = 25;  $fsa[34]['ATV'] = 25;
        $fsa[30]['TST'] = 26;  $fsa[31]['TST'] = 26;  $fsa[32]['TST'] = 26;  $fsa[33]['TST'] = 26;  $fsa[34]['TST'] = 26;
        $fsa[30]['SBT'] = 100; $fsa[31]['SBT'] = 100; $fsa[32]['SBT'] = 100; $fsa[33]['SBT'] = 100; $fsa[34]['SBT'] = 100;
        $fsa[30]['SRE'] = 100; $fsa[31]['SRE'] = 100; $fsa[32]['SRE'] = 100; $fsa[33]['SRE'] = 100; $fsa[34]['SRE'] = 100;
        $fsa[30]['SUM'] = 27;  $fsa[31]['SUM'] = 27;  $fsa[32]['SUM'] = 27;  $fsa[33]['SUM'] = 27;  $fsa[34]['SUM'] = 27;
        $fsa[30]['LX']  = 28;  $fsa[31]['LX']  = 28;  $fsa[32]['LX']  = 28;  $fsa[33]['LX']  = 28;  $fsa[34]['LX']  = 28;
        $fsa[30]['IMM'] = 100; $fsa[31]['IMM'] = 100; $fsa[32]['IMM'] = 100; $fsa[33]['IMM'] = 100; $fsa[34]['IMM'] = 100;
        $fsa[30]['SES'] = 100; $fsa[31]['SES'] = 100; $fsa[32]['SES'] = 100; $fsa[33]['SES'] = 100; $fsa[34]['SES'] = 100;
        $fsa[30]['CRS'] = 100; $fsa[31]['CRS'] = 100; $fsa[32]['CRS'] = 100; $fsa[33]['CRS'] = 100; $fsa[34]['CRS'] = 100;
        $fsa[30]['CSU'] = 100; $fsa[31]['CSU'] = 100; $fsa[32]['CSU'] = 100; $fsa[33]['CSU'] = 100; $fsa[34]['CSU'] = 100;
        $fsa[30]['MKS'] = 100; $fsa[31]['MKS'] = 100; $fsa[32]['MKS'] = 100; $fsa[33]['MKS'] = 100; $fsa[34]['MKS'] = 100;
        $fsa[30]['DEG'] = 100; $fsa[31]['DEG'] = 100; $fsa[32]['DEG'] = 100; $fsa[33]['DEG'] = 100; $fsa[34]['DEG'] = 100;
        $fsa[30]['FOS'] = 100; $fsa[31]['FOS'] = 100; $fsa[32]['FOS'] = 100; $fsa[33]['FOS'] = 100; $fsa[34]['FOS'] = 100;
        $fsa[30]['SE']  = 100; $fsa[31]['SE']  = 100; $fsa[32]['SE']  = 100; $fsa[33]['SE']  = 100; $fsa[34]['SE']  = 100;
        $fsa[30]['GE']  = 100; $fsa[31]['GE']  = 100; $fsa[32]['GE']  = 100; $fsa[33]['GE']  = 100; $fsa[34]['GE']  = 100;
        $fsa[30]['IEA'] = 100; $fsa[31]['IEA'] = 100; $fsa[32]['IEA'] = 100; $fsa[33]['IEA'] = 100; $fsa[34]['IEA'] = 100;

        $fsa[35]['ISA'] = 100; $fsa[36]['ISA'] = 100; $fsa[37]['ISA'] = 100; $fsa[38]['ISA'] = 100; $fsa[39]['ISA'] = 100;
        $fsa[35]['GS']  = 100; $fsa[36]['GS']  = 100; $fsa[37]['GS']  = 100; $fsa[38]['GS']  = 100; $fsa[39]['GS']  = 100;
        $fsa[35]['ST']  = 100; $fsa[36]['ST']  = 100; $fsa[37]['ST']  = 100; $fsa[38]['ST']  = 100; $fsa[39]['ST']  = 3;
        $fsa[35]['BGN'] = 100; $fsa[36]['BGN'] = 100; $fsa[37]['BGN'] = 100; $fsa[38]['BGN'] = 100; $fsa[39]['BGN'] = 100;
        $fsa[35]['ERP'] = 100; $fsa[36]['ERP'] = 100; $fsa[37]['ERP'] = 100; $fsa[38]['ERP'] = 100; $fsa[39]['ERP'] = 100;
        $fsa[35]['REF'] = 100; $fsa[36]['REF'] = 100; $fsa[37]['REF'] = 100; $fsa[38]['REF'] = 100; $fsa[39]['REF'] = 100;
        $fsa[35]['DMG'] = 100; $fsa[36]['DMG'] = 100; $fsa[37]['DMG'] = 100; $fsa[38]['DMG'] = 100; $fsa[39]['DMG'] = 100;
        $fsa[35]['LUI'] = 100; $fsa[36]['LUI'] = 100; $fsa[37]['LUI'] = 100; $fsa[38]['LUI'] = 100; $fsa[39]['LUI'] = 100;
        $fsa[35]['IND'] = 100; $fsa[36]['IND'] = 100; $fsa[37]['IND'] = 100; $fsa[38]['IND'] = 100; $fsa[39]['IND'] = 100;
        $fsa[35]['DTP'] = 100; $fsa[36]['DTP'] = 100; $fsa[37]['DTP'] = 100; $fsa[38]['DTP'] = 100; $fsa[39]['DTP'] = 100;
        $fsa[35]['RAP'] = 100; $fsa[36]['RAP'] = 100; $fsa[37]['RAP'] = 100; $fsa[38]['RAP'] = 100; $fsa[39]['RAP'] = 100;
        $fsa[35]['PCL'] = 100; $fsa[36]['PCL'] = 100; $fsa[37]['PCL'] = 100; $fsa[38]['PCL'] = 100; $fsa[39]['PCL'] = 100;
        $fsa[35]['NTE'] = 64;  $fsa[36]['NTE'] = 36;  $fsa[37]['NTE'] = 100; $fsa[38]['NTE'] = 41;  $fsa[39]['NTE'] = 100;
        $fsa[35]['N1']  = 100; $fsa[36]['N1']  = 100; $fsa[37]['N1']  = 100; $fsa[38]['N1']  = 42;  $fsa[39]['N1']  = 100;
        $fsa[35]['N2']  = 100; $fsa[36]['N2']  = 100; $fsa[37]['N2']  = 100; $fsa[38]['N2']  = 100; $fsa[39]['N2']  = 100;
        $fsa[35]['N3']  = 100; $fsa[36]['N3']  = 100; $fsa[37]['N3']  = 100; $fsa[38]['N3']  = 43;  $fsa[39]['N3']  = 100;
        $fsa[35]['N4']  = 100; $fsa[36]['N4']  = 100; $fsa[37]['N4']  = 100; $fsa[38]['N4']  = 44;  $fsa[39]['N4']  = 100;
        $fsa[35]['PER'] = 100; $fsa[36]['PER'] = 100; $fsa[37]['PER'] = 100; $fsa[38]['PER'] = 100; $fsa[39]['PER'] = 100;
        $fsa[35]['IN1'] = 100; $fsa[36]['IN1'] = 100; $fsa[37]['IN1'] = 100; $fsa[38]['IN1'] = 100; $fsa[39]['IN1'] = 100;
        $fsa[35]['IN2'] = 100; $fsa[36]['IN2'] = 100; $fsa[37]['IN2'] = 100; $fsa[38]['IN2'] = 100; $fsa[39]['IN2'] = 100;
        $fsa[35]['SST'] = 100; $fsa[36]['SST'] = 100; $fsa[37]['SST'] = 100; $fsa[38]['SST'] = 100; $fsa[39]['SST'] = 100;
        $fsa[35]['SSE'] = 100; $fsa[36]['SSE'] = 100; $fsa[37]['SSE'] = 100; $fsa[38]['SSE'] = 40;  $fsa[39]['SSE'] = 100;
        $fsa[35]['ATV'] = 100; $fsa[36]['ATV'] = 100; $fsa[37]['ATV'] = 100; $fsa[38]['ATV'] = 100; $fsa[39]['ATV'] = 100;
        $fsa[35]['TST'] = 26;  $fsa[36]['TST'] = 100; $fsa[37]['TST'] = 100; $fsa[38]['TST'] = 100; $fsa[39]['TST'] = 100;
        $fsa[35]['SBT'] = 35;  $fsa[36]['SBT'] = 100; $fsa[37]['SBT'] = 100; $fsa[38]['SBT'] = 100; $fsa[39]['SBT'] = 100;
        $fsa[35]['SRE'] = 63;  $fsa[36]['SRE'] = 100; $fsa[37]['SRE'] = 100; $fsa[38]['SRE'] = 100; $fsa[39]['SRE'] = 100;
        $fsa[35]['SUM'] = 27;  $fsa[36]['SUM'] = 27;  $fsa[37]['SUM'] = 100; $fsa[38]['SUM'] = 45;  $fsa[39]['SUM'] = 100;
        $fsa[35]['LX']  = 28;  $fsa[36]['LX']  = 28;  $fsa[37]['LX']  = 100; $fsa[38]['LX']  = 100; $fsa[39]['LX']  = 100;
        $fsa[35]['IMM'] = 100; $fsa[36]['IMM'] = 100; $fsa[37]['IMM'] = 37;  $fsa[38]['IMM'] = 100; $fsa[39]['IMM'] = 100;
        $fsa[35]['SES'] = 100; $fsa[36]['SES'] = 100; $fsa[37]['SES'] = 38;  $fsa[38]['SES'] = 38;  $fsa[39]['SES'] = 100;
        $fsa[35]['CRS'] = 100; $fsa[36]['CRS'] = 100; $fsa[37]['CRS'] = 100; $fsa[38]['CRS'] = 46;  $fsa[39]['CRS'] = 100;
        $fsa[35]['CSU'] = 100; $fsa[36]['CSU'] = 100; $fsa[37]['CSU'] = 100; $fsa[38]['CSU'] = 100; $fsa[39]['CSU'] = 100;
        $fsa[35]['MKS'] = 100; $fsa[36]['MKS'] = 100; $fsa[37]['MKS'] = 100; $fsa[38]['MKS'] = 100; $fsa[39]['MKS'] = 100;
        $fsa[35]['DEG'] = 100; $fsa[36]['DEG'] = 100; $fsa[37]['DEG'] = 100; $fsa[38]['DEG'] = 47;  $fsa[39]['DEG'] = 100;
        $fsa[35]['FOS'] = 100; $fsa[36]['FOS'] = 100; $fsa[37]['FOS'] = 100; $fsa[38]['FOS'] = 100; $fsa[39]['FOS'] = 100;
        $fsa[35]['SE']  = 100; $fsa[36]['SE']  = 100; $fsa[37]['SE']  = 39;  $fsa[38]['SE']  = 39;  $fsa[39]['SE']  = 100;
        $fsa[35]['GE']  = 100; $fsa[36]['GE']  = 100; $fsa[37]['GE']  = 100; $fsa[38]['GE']  = 100; $fsa[39]['GE']  = 48;
        $fsa[35]['IEA'] = 100; $fsa[36]['IEA'] = 100; $fsa[37]['IEA'] = 100; $fsa[38]['IEA'] = 100; $fsa[39]['IEA'] = 100;

        $fsa[40]['ISA'] = 100; $fsa[41]['ISA'] = 100; $fsa[42]['ISA'] = 100; $fsa[43]['ISA'] = 100; $fsa[44]['ISA'] = 100;
        $fsa[40]['GS']  = 100; $fsa[41]['GS']  = 100; $fsa[42]['GS']  = 100; $fsa[43]['GS']  = 100; $fsa[44]['GS']  = 100;
        $fsa[40]['ST']  = 100; $fsa[41]['ST']  = 100; $fsa[42]['ST']  = 100; $fsa[43]['ST']  = 100; $fsa[44]['ST']  = 100;
        $fsa[40]['BGN'] = 100; $fsa[41]['BGN'] = 100; $fsa[42]['BGN'] = 100; $fsa[43]['BGN'] = 100; $fsa[44]['BGN'] = 100;
        $fsa[40]['ERP'] = 100; $fsa[41]['ERP'] = 100; $fsa[42]['ERP'] = 100; $fsa[43]['ERP'] = 100; $fsa[44]['ERP'] = 100;
        $fsa[40]['REF'] = 100; $fsa[41]['REF'] = 100; $fsa[42]['REF'] = 100; $fsa[43]['REF'] = 100; $fsa[44]['REF'] = 100;
        $fsa[40]['DMG'] = 100; $fsa[41]['DMG'] = 100; $fsa[42]['DMG'] = 100; $fsa[43]['DMG'] = 100; $fsa[44]['DMG'] = 100;
        $fsa[40]['LUI'] = 100; $fsa[41]['LUI'] = 100; $fsa[42]['LUI'] = 100; $fsa[43]['LUI'] = 100; $fsa[44]['LUI'] = 100;
        $fsa[40]['IND'] = 100; $fsa[41]['IND'] = 100; $fsa[42]['IND'] = 100; $fsa[43]['IND'] = 100; $fsa[44]['IND'] = 100;
        $fsa[40]['DTP'] = 100; $fsa[41]['DTP'] = 100; $fsa[42]['DTP'] = 100; $fsa[43]['DTP'] = 100; $fsa[44]['DTP'] = 100;
        $fsa[40]['RAP'] = 100; $fsa[41]['RAP'] = 100; $fsa[42]['RAP'] = 100; $fsa[43]['RAP'] = 100; $fsa[44]['RAP'] = 100;
        $fsa[40]['PCL'] = 100; $fsa[41]['PCL'] = 100; $fsa[42]['PCL'] = 100; $fsa[43]['PCL'] = 100; $fsa[44]['PCL'] = 100;
        $fsa[40]['NTE'] = 41;  $fsa[41]['NTE'] = 41;  $fsa[42]['NTE'] = 100; $fsa[43]['NTE'] = 100; $fsa[44]['NTE'] = 100;
        $fsa[40]['N1']  = 42;  $fsa[41]['N1']  = 42;  $fsa[42]['N1']  = 100; $fsa[43]['N1']  = 100; $fsa[44]['N1']  = 100;
        $fsa[40]['N2']  = 100; $fsa[41]['N2']  = 100; $fsa[42]['N2']  = 100; $fsa[43]['N2']  = 100; $fsa[44]['N2']  = 100;
        $fsa[40]['N3']  = 43;  $fsa[41]['N3']  = 43;  $fsa[42]['N3']  = 43;  $fsa[43]['N3']  = 100; $fsa[44]['N3']  = 100;
        $fsa[40]['N4']  = 44;  $fsa[41]['N4']  = 44;  $fsa[42]['N4']  = 44;  $fsa[43]['N4']  = 44;  $fsa[44]['N4']  = 100;
        $fsa[40]['PER'] = 100; $fsa[41]['PER'] = 100; $fsa[42]['PER'] = 100; $fsa[43]['PER'] = 100; $fsa[44]['PER'] = 100;
        $fsa[40]['IN1'] = 100; $fsa[41]['IN1'] = 100; $fsa[42]['IN1'] = 100; $fsa[43]['IN1'] = 100; $fsa[44]['IN1'] = 100;
        $fsa[40]['IN2'] = 100; $fsa[41]['IN2'] = 100; $fsa[42]['IN2'] = 100; $fsa[43]['IN2'] = 100; $fsa[44]['IN2'] = 100;
        $fsa[40]['SST'] = 100; $fsa[41]['SST'] = 100; $fsa[42]['SST'] = 100; $fsa[43]['SST'] = 100; $fsa[44]['SST'] = 100;
        $fsa[40]['SSE'] = 100; $fsa[41]['SSE'] = 100; $fsa[42]['SSE'] = 100; $fsa[43]['SSE'] = 100; $fsa[44]['SSE'] = 100;
        $fsa[40]['ATV'] = 100; $fsa[41]['ATV'] = 100; $fsa[42]['ATV'] = 100; $fsa[43]['ATV'] = 100; $fsa[44]['ATV'] = 100;
        $fsa[40]['TST'] = 100; $fsa[41]['TST'] = 100; $fsa[42]['TST'] = 100; $fsa[43]['TST'] = 100; $fsa[44]['TST'] = 100;
        $fsa[40]['SBT'] = 100; $fsa[41]['SBT'] = 100; $fsa[42]['SBT'] = 100; $fsa[43]['SBT'] = 100; $fsa[44]['SBT'] = 100;
        $fsa[40]['SRE'] = 100; $fsa[41]['SRE'] = 100; $fsa[42]['SRE'] = 100; $fsa[43]['SRE'] = 100; $fsa[44]['SRE'] = 100;
        $fsa[40]['SUM'] = 45;  $fsa[41]['SUM'] = 45;  $fsa[42]['SUM'] = 45;  $fsa[43]['SUM'] = 45;  $fsa[44]['SUM'] = 45;
        $fsa[40]['LX']  = 100; $fsa[41]['LX']  = 100; $fsa[42]['LX']  = 100; $fsa[43]['LX']  = 100; $fsa[44]['LX']  = 100;
        $fsa[40]['IMM'] = 100; $fsa[41]['IMM'] = 100; $fsa[42]['IMM'] = 100; $fsa[43]['IMM'] = 100; $fsa[44]['IMM'] = 100;
        $fsa[40]['SES'] = 38;  $fsa[41]['SES'] = 38;  $fsa[42]['SES'] = 38;  $fsa[43]['SES'] = 38;  $fsa[44]['SES'] = 38;
        $fsa[40]['CRS'] = 46;  $fsa[41]['CRS'] = 46;  $fsa[42]['CRS'] = 46;  $fsa[43]['CRS'] = 46;  $fsa[44]['CRS'] = 46;
        $fsa[40]['CSU'] = 100; $fsa[41]['CSU'] = 100; $fsa[42]['CSU'] = 100; $fsa[43]['CSU'] = 100; $fsa[44]['CSU'] = 100;
        $fsa[40]['MKS'] = 100; $fsa[41]['MKS'] = 100; $fsa[42]['MKS'] = 100; $fsa[43]['MKS'] = 100; $fsa[44]['MKS'] = 100;
        $fsa[40]['DEG'] = 47;  $fsa[41]['DEG'] = 47;  $fsa[42]['DEG'] = 47;  $fsa[43]['DEG'] = 47;  $fsa[44]['DEG'] = 47;
        $fsa[40]['FOS'] = 100; $fsa[41]['FOS'] = 100; $fsa[42]['FOS'] = 100; $fsa[43]['FOS'] = 100; $fsa[44]['FOS'] = 100;
        $fsa[40]['SE']  = 39;  $fsa[41]['SE']  = 39;  $fsa[42]['SE']  = 39;  $fsa[43]['SE']  = 39;  $fsa[44]['SE']  = 39;
        $fsa[40]['GE']  = 100; $fsa[41]['GE']  = 100; $fsa[42]['GE']  = 100; $fsa[43]['GE']  = 100; $fsa[44]['GE']  = 100;
        $fsa[40]['IEA'] = 100; $fsa[41]['IEA'] = 100; $fsa[42]['IEA'] = 100; $fsa[43]['IEA'] = 100; $fsa[44]['IEA'] = 100;

        $fsa[45]['ISA'] = 100; $fsa[46]['ISA'] = 100; $fsa[47]['ISA'] = 100; $fsa[48]['ISA'] = 100; $fsa[49]['ISA'] = 100;
        $fsa[45]['GS']  = 100; $fsa[46]['GS']  = 100; $fsa[47]['GS']  = 100; $fsa[48]['GS']  = 2;   $fsa[49]['GS']  = 100;
        $fsa[45]['ST']  = 100; $fsa[46]['ST']  = 100; $fsa[47]['ST']  = 100; $fsa[48]['ST']  = 100; $fsa[49]['ST']  = 100;
        $fsa[45]['BGN'] = 100; $fsa[46]['BGN'] = 100; $fsa[47]['BGN'] = 100; $fsa[48]['BGN'] = 100; $fsa[49]['BGN'] = 100;
        $fsa[45]['ERP'] = 100; $fsa[46]['ERP'] = 100; $fsa[47]['ERP'] = 100; $fsa[48]['ERP'] = 100; $fsa[49]['ERP'] = 100;
        $fsa[45]['REF'] = 100; $fsa[46]['REF'] = 50;  $fsa[47]['REF'] = 100; $fsa[48]['REF'] = 100; $fsa[49]['REF'] = 100;
        $fsa[45]['DMG'] = 100; $fsa[46]['DMG'] = 100; $fsa[47]['DMG'] = 100; $fsa[48]['DMG'] = 100; $fsa[49]['DMG'] = 100;
        $fsa[45]['LUI'] = 100; $fsa[46]['LUI'] = 52;  $fsa[47]['LUI'] = 100; $fsa[48]['LUI'] = 100; $fsa[49]['LUI'] = 100;
        $fsa[45]['IND'] = 100; $fsa[46]['IND'] = 100; $fsa[47]['IND'] = 100; $fsa[48]['IND'] = 100; $fsa[49]['IND'] = 100;
        $fsa[45]['DTP'] = 100; $fsa[46]['DTP'] = 100; $fsa[47]['DTP'] = 100; $fsa[48]['DTP'] = 100; $fsa[49]['DTP'] = 100;
        $fsa[45]['RAP'] = 100; $fsa[46]['RAP'] = 53;  $fsa[47]['RAP'] = 100; $fsa[48]['RAP'] = 100; $fsa[49]['RAP'] = 100;
        $fsa[45]['PCL'] = 100; $fsa[46]['PCL'] = 100; $fsa[47]['PCL'] = 100; $fsa[48]['PCL'] = 100; $fsa[49]['PCL'] = 100;
        $fsa[45]['NTE'] = 49;  $fsa[46]['NTE'] = 54;  $fsa[47]['NTE'] = 61;  $fsa[48]['NTE'] = 100; $fsa[49]['NTE'] = 49;
        $fsa[45]['N1']  = 100; $fsa[46]['N1']  = 55;  $fsa[47]['N1']  = 60;  $fsa[48]['N1']  = 100; $fsa[49]['N1']  = 100;
        $fsa[45]['N2']  = 100; $fsa[46]['N2']  = 100; $fsa[47]['N2']  = 100; $fsa[48]['N2']  = 100; $fsa[49]['N2']  = 100;
        $fsa[45]['N3']  = 100; $fsa[46]['N3']  = 100; $fsa[47]['N3']  = 100; $fsa[48]['N3']  = 100; $fsa[49]['N3']  = 100;
        $fsa[45]['N4']  = 100; $fsa[46]['N4']  = 56;  $fsa[47]['N4']  = 100; $fsa[48]['N4']  = 100; $fsa[49]['N4']  = 100;
        $fsa[45]['PER'] = 100; $fsa[46]['PER'] = 100; $fsa[47]['PER'] = 100; $fsa[48]['PER'] = 100; $fsa[49]['PER'] = 100;
        $fsa[45]['IN1'] = 100; $fsa[46]['IN1'] = 100; $fsa[47]['IN1'] = 100; $fsa[48]['IN1'] = 100; $fsa[49]['IN1'] = 100;
        $fsa[45]['IN2'] = 100; $fsa[46]['IN2'] = 100; $fsa[47]['IN2'] = 100; $fsa[48]['IN2'] = 100; $fsa[49]['IN2'] = 100;
        $fsa[45]['SST'] = 100; $fsa[46]['SST'] = 100; $fsa[47]['SST'] = 100; $fsa[48]['SST'] = 100; $fsa[49]['SST'] = 100;
        $fsa[45]['SSE'] = 100; $fsa[46]['SSE'] = 100; $fsa[47]['SSE'] = 100; $fsa[48]['SSE'] = 100; $fsa[49]['SSE'] = 100;
        $fsa[45]['ATV'] = 100; $fsa[46]['ATV'] = 100; $fsa[47]['ATV'] = 100; $fsa[48]['ATV'] = 100; $fsa[49]['ATV'] = 100;
        $fsa[45]['TST'] = 100; $fsa[46]['TST'] = 100; $fsa[47]['TST'] = 100; $fsa[48]['TST'] = 100; $fsa[49]['TST'] = 100;
        $fsa[45]['SBT'] = 100; $fsa[46]['SBT'] = 100; $fsa[47]['SBT'] = 100; $fsa[48]['SBT'] = 100; $fsa[49]['SBT'] = 100;
        $fsa[45]['SRE'] = 100; $fsa[46]['SRE'] = 100; $fsa[47]['SRE'] = 100; $fsa[48]['SRE'] = 100; $fsa[49]['SRE'] = 100;
        $fsa[45]['SUM'] = 45;  $fsa[46]['SUM'] = 45;  $fsa[47]['SUM'] = 58;  $fsa[48]['SUM'] = 100; $fsa[49]['SUM'] = 45;
        $fsa[45]['LX']  = 100; $fsa[46]['LX']  = 100; $fsa[47]['LX']  = 100; $fsa[48]['LX']  = 100; $fsa[49]['LX']  = 100;
        $fsa[45]['IMM'] = 100; $fsa[46]['IMM'] = 100; $fsa[47]['IMM'] = 100; $fsa[48]['IMM'] = 100; $fsa[49]['IMM'] = 100;
        $fsa[45]['SES'] = 38;  $fsa[46]['SES'] = 38;  $fsa[47]['SES'] = 38;  $fsa[48]['SES'] = 100; $fsa[49]['SES'] = 38;
        $fsa[45]['CRS'] = 46;  $fsa[46]['CRS'] = 46;  $fsa[47]['CRS'] = 100; $fsa[48]['CRS'] = 100; $fsa[49]['CRS'] = 46;
        $fsa[45]['CSU'] = 100; $fsa[46]['CSU'] = 51;  $fsa[47]['CSU'] = 100; $fsa[48]['CSU'] = 100; $fsa[49]['CSU'] = 100;
        $fsa[45]['MKS'] = 100; $fsa[46]['MKS'] = 57;  $fsa[47]['MKS'] = 100; $fsa[48]['MKS'] = 100; $fsa[49]['MKS'] = 100;
        $fsa[45]['DEG'] = 47;  $fsa[46]['DEG'] = 47;  $fsa[47]['DEG'] = 47;  $fsa[48]['DEG'] = 100; $fsa[49]['DEG'] = 47;
        $fsa[45]['FOS'] = 100; $fsa[46]['FOS'] = 100; $fsa[47]['FOS'] = 59;  $fsa[48]['FOS'] = 100; $fsa[49]['FOS'] = 100;
        $fsa[45]['SE']  = 39;  $fsa[46]['SE']  = 39;  $fsa[47]['SE']  = 39;  $fsa[48]['SE']  = 100; $fsa[49]['SE']  = 39;
        $fsa[45]['GE']  = 100; $fsa[46]['GE']  = 100; $fsa[47]['GE']  = 100; $fsa[48]['GE']  = 100; $fsa[49]['GE']  = 100;
        $fsa[45]['IEA'] = 100; $fsa[46]['IEA'] = 100; $fsa[47]['IEA'] = 100; $fsa[48]['IEA'] = 0;   $fsa[49]['IEA'] = 100;

        $fsa[50]['ISA'] = 100; $fsa[51]['ISA'] = 100; $fsa[52]['ISA'] = 100; $fsa[53]['ISA'] = 100; $fsa[54]['ISA'] = 100;
        $fsa[50]['GS']  = 100; $fsa[51]['GS']  = 100; $fsa[52]['GS']  = 100; $fsa[53]['GS']  = 100; $fsa[54]['GS']  = 100;
        $fsa[50]['ST']  = 100; $fsa[51]['ST']  = 100; $fsa[52]['ST']  = 100; $fsa[53]['ST']  = 100; $fsa[54]['ST']  = 100;
        $fsa[50]['BGN'] = 100; $fsa[51]['BGN'] = 100; $fsa[52]['BGN'] = 100; $fsa[53]['BGN'] = 100; $fsa[54]['BGN'] = 100;
        $fsa[50]['ERP'] = 100; $fsa[51]['ERP'] = 100; $fsa[52]['ERP'] = 100; $fsa[53]['ERP'] = 100; $fsa[54]['ERP'] = 100;
        $fsa[50]['REF'] = 50;  $fsa[51]['REF'] = 100; $fsa[52]['REF'] = 100; $fsa[53]['REF'] = 100; $fsa[54]['REF'] = 100;
        $fsa[50]['DMG'] = 100; $fsa[51]['DMG'] = 100; $fsa[52]['DMG'] = 100; $fsa[53]['DMG'] = 100; $fsa[54]['DMG'] = 100;
        $fsa[50]['LUI'] = 52;  $fsa[51]['LUI'] = 52;  $fsa[52]['LUI'] = 52;  $fsa[53]['LUI'] = 100; $fsa[54]['LUI'] = 100;
        $fsa[50]['IND'] = 100; $fsa[51]['IND'] = 100; $fsa[52]['IND'] = 100; $fsa[53]['IND'] = 100; $fsa[54]['IND'] = 100;
        $fsa[50]['DTP'] = 100; $fsa[51]['DTP'] = 100; $fsa[52]['DTP'] = 100; $fsa[53]['DTP'] = 100; $fsa[54]['DTP'] = 100;
        $fsa[50]['RAP'] = 53;  $fsa[51]['RAP'] = 53;  $fsa[52]['RAP'] = 100; $fsa[53]['RAP'] = 53;  $fsa[54]['RAP'] = 100;
        $fsa[50]['PCL'] = 100; $fsa[51]['PCL'] = 100; $fsa[52]['PCL'] = 100; $fsa[53]['PCL'] = 100; $fsa[54]['PCL'] = 100;
        $fsa[50]['NTE'] = 54;  $fsa[51]['NTE'] = 54;  $fsa[52]['NTE'] = 54;  $fsa[53]['NTE'] = 54;  $fsa[54]['NTE'] = 54;
        $fsa[50]['N1']  = 55;  $fsa[51]['N1']  = 55;  $fsa[52]['N1']  = 55;  $fsa[53]['N1']  = 55;  $fsa[54]['N1']  = 55;
        $fsa[50]['N2']  = 100; $fsa[51]['N2']  = 100; $fsa[52]['N2']  = 100; $fsa[53]['N2']  = 100; $fsa[54]['N2']  = 100;
        $fsa[50]['N3']  = 100; $fsa[51]['N3']  = 100; $fsa[52]['N3']  = 100; $fsa[53]['N3']  = 100; $fsa[54]['N3']  = 100;
        $fsa[50]['N4']  = 56;  $fsa[51]['N4']  = 56;  $fsa[52]['N4']  = 56;  $fsa[53]['N4']  = 56;  $fsa[54]['N4']  = 56;
        $fsa[50]['PER'] = 100; $fsa[51]['PER'] = 100; $fsa[52]['PER'] = 100; $fsa[53]['PER'] = 100; $fsa[54]['PER'] = 100;
        $fsa[50]['IN1'] = 100; $fsa[51]['IN1'] = 100; $fsa[52]['IN1'] = 100; $fsa[53]['IN1'] = 100; $fsa[54]['IN1'] = 100;
        $fsa[50]['IN2'] = 100; $fsa[51]['IN2'] = 100; $fsa[52]['IN2'] = 100; $fsa[53]['IN2'] = 100; $fsa[54]['IN2'] = 100;
        $fsa[50]['SST'] = 100; $fsa[51]['SST'] = 100; $fsa[52]['SST'] = 100; $fsa[53]['SST'] = 100; $fsa[54]['SST'] = 100;
        $fsa[50]['SSE'] = 100; $fsa[51]['SSE'] = 100; $fsa[52]['SSE'] = 100; $fsa[53]['SSE'] = 100; $fsa[54]['SSE'] = 100;
        $fsa[50]['ATV'] = 100; $fsa[51]['ATV'] = 100; $fsa[52]['ATV'] = 100; $fsa[53]['ATV'] = 100; $fsa[54]['ATV'] = 100;
        $fsa[50]['TST'] = 100; $fsa[51]['TST'] = 100; $fsa[52]['TST'] = 100; $fsa[53]['TST'] = 100; $fsa[54]['TST'] = 100;
        $fsa[50]['SBT'] = 100; $fsa[51]['SBT'] = 100; $fsa[52]['SBT'] = 100; $fsa[53]['SBT'] = 100; $fsa[54]['SBT'] = 100;
        $fsa[50]['SRE'] = 100; $fsa[51]['SRE'] = 100; $fsa[52]['SRE'] = 100; $fsa[53]['SRE'] = 100; $fsa[54]['SRE'] = 100;
        $fsa[50]['SUM'] = 100; $fsa[51]['SUM'] = 100; $fsa[52]['SUM'] = 100; $fsa[53]['SUM'] = 100; $fsa[54]['SUM'] = 100;
        $fsa[50]['LX']  = 100; $fsa[51]['LX']  = 100; $fsa[52]['LX']  = 100; $fsa[53]['LX']  = 100; $fsa[54]['LX']  = 100;
        $fsa[50]['IMM'] = 100; $fsa[51]['IMM'] = 100; $fsa[52]['IMM'] = 100; $fsa[53]['IMM'] = 100; $fsa[54]['IMM'] = 100;
        $fsa[50]['SES'] = 38;  $fsa[51]['SES'] = 38;  $fsa[52]['SES'] = 38;  $fsa[53]['SES'] = 38;  $fsa[54]['SES'] = 38;
        $fsa[50]['CRS'] = 46;  $fsa[51]['CRS'] = 46;  $fsa[52]['CRS'] = 46;  $fsa[53]['CRS'] = 46;  $fsa[54]['CRS'] = 46;
        $fsa[50]['CSU'] = 51;  $fsa[51]['CSU'] = 100; $fsa[52]['CSU'] = 100; $fsa[53]['CSU'] = 100; $fsa[54]['CSU'] = 100;
        $fsa[50]['MKS'] = 57;  $fsa[51]['MKS'] = 57;  $fsa[52]['MKS'] = 57;  $fsa[53]['MKS'] = 57;  $fsa[54]['MKS'] = 57;
        $fsa[50]['DEG'] = 49;  $fsa[51]['DEG'] = 47;  $fsa[52]['DEG'] = 47;  $fsa[53]['DEG'] = 47;  $fsa[54]['DEG'] = 47;
        $fsa[50]['FOS'] = 100; $fsa[51]['FOS'] = 100; $fsa[52]['FOS'] = 100; $fsa[53]['FOS'] = 100; $fsa[54]['FOS'] = 100;
        $fsa[50]['SE']  = 39;  $fsa[51]['SE']  = 39;  $fsa[52]['SE']  = 39;  $fsa[53]['SE']  = 39;  $fsa[54]['SE']  = 39;
        $fsa[50]['GE']  = 100; $fsa[51]['GE']  = 100; $fsa[52]['GE']  = 100; $fsa[53]['GE']  = 100; $fsa[54]['GE']  = 100;
        $fsa[50]['IEA'] = 100; $fsa[51]['IEA'] = 100; $fsa[52]['IEA'] = 100; $fsa[53]['IEA'] = 100; $fsa[54]['IEA'] = 100;

        $fsa[55]['ISA'] = 100; $fsa[56]['ISA'] = 100; $fsa[57]['ISA'] = 100; $fsa[58]['ISA'] = 100; $fsa[59]['ISA'] = 100;
        $fsa[55]['GS']  = 100; $fsa[56]['GS']  = 100; $fsa[57]['GS']  = 100; $fsa[58]['GS']  = 100; $fsa[59]['GS']  = 100;
        $fsa[55]['ST']  = 100; $fsa[56]['ST']  = 100; $fsa[57]['ST']  = 100; $fsa[58]['ST']  = 100; $fsa[59]['ST']  = 100;
        $fsa[55]['BGN'] = 100; $fsa[56]['BGN'] = 100; $fsa[57]['BGN'] = 100; $fsa[58]['BGN'] = 100; $fsa[59]['BGN'] = 100;
        $fsa[55]['ERP'] = 100; $fsa[56]['ERP'] = 100; $fsa[57]['ERP'] = 100; $fsa[58]['ERP'] = 100; $fsa[59]['ERP'] = 100;
        $fsa[55]['REF'] = 100; $fsa[56]['REF'] = 100; $fsa[57]['REF'] = 100; $fsa[58]['REF'] = 100; $fsa[59]['REF'] = 100;
        $fsa[55]['DMG'] = 100; $fsa[56]['DMG'] = 100; $fsa[57]['DMG'] = 100; $fsa[58]['DMG'] = 100; $fsa[59]['DMG'] = 100;
        $fsa[55]['LUI'] = 100; $fsa[56]['LUI'] = 100; $fsa[57]['LUI'] = 62;  $fsa[58]['LUI'] = 100; $fsa[59]['LUI'] = 100;
        $fsa[55]['IND'] = 100; $fsa[56]['IND'] = 100; $fsa[57]['IND'] = 100; $fsa[58]['IND'] = 100; $fsa[59]['IND'] = 100;
        $fsa[55]['DTP'] = 100; $fsa[56]['DTP'] = 100; $fsa[57]['DTP'] = 100; $fsa[58]['DTP'] = 100; $fsa[59]['DTP'] = 100;
        $fsa[55]['RAP'] = 100; $fsa[56]['RAP'] = 100; $fsa[57]['RAP'] = 100; $fsa[58]['RAP'] = 100; $fsa[59]['RAP'] = 100;
        $fsa[55]['PCL'] = 100; $fsa[56]['PCL'] = 100; $fsa[57]['PCL'] = 100; $fsa[58]['PCL'] = 100; $fsa[59]['PCL'] = 100;
        $fsa[55]['NTE'] = 100; $fsa[56]['NTE'] = 100; $fsa[57]['NTE'] = 100; $fsa[58]['NTE'] = 61;  $fsa[59]['NTE'] = 61;
        $fsa[55]['N1']  = 100; $fsa[56]['N1']  = 100; $fsa[57]['N1']  = 100; $fsa[58]['N1']  = 60;  $fsa[59]['N1']  = 60;
        $fsa[55]['N2']  = 100; $fsa[56]['N2']  = 100; $fsa[57]['N2']  = 100; $fsa[58]['N2']  = 100; $fsa[59]['N2']  = 100;
        $fsa[55]['N3']  = 100; $fsa[56]['N3']  = 100; $fsa[57]['N3']  = 100; $fsa[58]['N3']  = 100; $fsa[59]['N3']  = 100;
        $fsa[55]['N4']  = 56;  $fsa[56]['N4']  = 100; $fsa[57]['N4']  = 100; $fsa[58]['N4']  = 100; $fsa[59]['N4']  = 100;
        $fsa[55]['PER'] = 100; $fsa[56]['PER'] = 100; $fsa[57]['PER'] = 100; $fsa[58]['PER'] = 100; $fsa[59]['PER'] = 100;
        $fsa[55]['IN1'] = 100; $fsa[56]['IN1'] = 100; $fsa[57]['IN1'] = 100; $fsa[58]['IN1'] = 100; $fsa[59]['IN1'] = 100;
        $fsa[55]['IN2'] = 100; $fsa[56]['IN2'] = 100; $fsa[57]['IN2'] = 100; $fsa[58]['IN2'] = 100; $fsa[59]['IN2'] = 100;
        $fsa[55]['SST'] = 100; $fsa[56]['SST'] = 100; $fsa[57]['SST'] = 100; $fsa[58]['SST'] = 100; $fsa[59]['SST'] = 100;
        $fsa[55]['SSE'] = 100; $fsa[56]['SSE'] = 100; $fsa[57]['SSE'] = 100; $fsa[58]['SSE'] = 100; $fsa[59]['SSE'] = 100;
        $fsa[55]['ATV'] = 100; $fsa[56]['ATV'] = 100; $fsa[57]['ATV'] = 100; $fsa[58]['ATV'] = 100; $fsa[59]['ATV'] = 100;
        $fsa[55]['TST'] = 100; $fsa[56]['TST'] = 100; $fsa[57]['TST'] = 100; $fsa[58]['TST'] = 100; $fsa[59]['TST'] = 100;
        $fsa[55]['SBT'] = 100; $fsa[56]['SBT'] = 100; $fsa[57]['SBT'] = 100; $fsa[58]['SBT'] = 100; $fsa[59]['SBT'] = 100;
        $fsa[55]['SRE'] = 100; $fsa[56]['SRE'] = 100; $fsa[57]['SRE'] = 100; $fsa[58]['SRE'] = 100; $fsa[59]['SRE'] = 100;
        $fsa[55]['SUM'] = 100; $fsa[56]['SUM'] = 100; $fsa[57]['SUM'] = 100; $fsa[58]['SUM'] = 58;  $fsa[59]['SUM'] = 100;
        $fsa[55]['LX']  = 100; $fsa[56]['LX']  = 100; $fsa[57]['LX']  = 100; $fsa[58]['LX']  = 100; $fsa[59]['LX']  = 100;
        $fsa[55]['IMM'] = 100; $fsa[56]['IMM'] = 100; $fsa[57]['IMM'] = 100; $fsa[58]['IMM'] = 100; $fsa[59]['IMM'] = 100;
        $fsa[55]['SES'] = 38;  $fsa[56]['SES'] = 38;  $fsa[57]['SES'] = 38;  $fsa[58]['SES'] = 38;  $fsa[59]['SES'] = 38;
        $fsa[55]['CRS'] = 46;  $fsa[56]['CRS'] = 46;  $fsa[57]['CRS'] = 46;  $fsa[58]['CRS'] = 100; $fsa[59]['CRS'] = 100;
        $fsa[55]['CSU'] = 100; $fsa[56]['CSU'] = 100; $fsa[57]['CSU'] = 100; $fsa[58]['CSU'] = 100; $fsa[59]['CSU'] = 100;
        $fsa[55]['MKS'] = 57;  $fsa[56]['MKS'] = 57;  $fsa[57]['MKS'] = 57;  $fsa[58]['MKS'] = 100; $fsa[59]['MKS'] = 100;
        $fsa[55]['DEG'] = 47;  $fsa[56]['DEG'] = 47;  $fsa[57]['DEG'] = 47;  $fsa[58]['DEG'] = 47;  $fsa[59]['DEG'] = 47;
        $fsa[55]['FOS'] = 100; $fsa[56]['FOS'] = 100; $fsa[57]['FOS'] = 100; $fsa[58]['FOS'] = 59;  $fsa[59]['FOS'] = 59;
        $fsa[55]['SE']  = 39;  $fsa[56]['SE']  = 39;  $fsa[57]['SE']  = 39;  $fsa[58]['SE']  = 39;  $fsa[59]['SE']  = 39;
        $fsa[55]['GE']  = 100; $fsa[56]['GE']  = 100; $fsa[57]['GE']  = 100; $fsa[58]['GE']  = 100; $fsa[59]['GE']  = 100;
        $fsa[55]['IEA'] = 100; $fsa[56]['IEA'] = 100; $fsa[57]['IEA'] = 100; $fsa[58]['IEA'] = 100; $fsa[59]['IEA'] = 100;

        $fsa[60]['ISA'] = 100; $fsa[61]['ISA'] = 100; $fsa[62]['ISA'] = 100; $fsa[63]['ISA'] = 100; $fsa[64]['ISA'] = 100;
        $fsa[60]['GS']  = 100; $fsa[61]['GS']  = 100; $fsa[62]['GS']  = 100; $fsa[63]['GS']  = 100; $fsa[64]['GS']  = 100;
        $fsa[60]['ST']  = 100; $fsa[61]['ST']  = 100; $fsa[62]['ST']  = 100; $fsa[63]['ST']  = 100; $fsa[64]['ST']  = 100;
        $fsa[60]['BGN'] = 100; $fsa[61]['BGN'] = 100; $fsa[62]['BGN'] = 100; $fsa[63]['BGN'] = 100; $fsa[64]['BGN'] = 100;
        $fsa[60]['ERP'] = 100; $fsa[61]['ERP'] = 100; $fsa[62]['ERP'] = 100; $fsa[63]['ERP'] = 100; $fsa[64]['ERP'] = 100;
        $fsa[60]['REF'] = 100; $fsa[61]['REF'] = 100; $fsa[62]['REF'] = 100; $fsa[63]['REF'] = 100; $fsa[64]['REF'] = 100;
        $fsa[60]['DMG'] = 100; $fsa[61]['DMG'] = 100; $fsa[62]['DMG'] = 100; $fsa[63]['DMG'] = 100; $fsa[64]['DMG'] = 100;
        $fsa[60]['LUI'] = 100; $fsa[61]['LUI'] = 100; $fsa[62]['LUI'] = 100; $fsa[63]['LUI'] = 100; $fsa[64]['LUI'] = 100;
        $fsa[60]['IND'] = 100; $fsa[61]['IND'] = 100; $fsa[62]['IND'] = 100; $fsa[63]['IND'] = 100; $fsa[64]['IND'] = 100;
        $fsa[60]['DTP'] = 100; $fsa[61]['DTP'] = 100; $fsa[62]['DTP'] = 100; $fsa[63]['DTP'] = 100; $fsa[64]['DTP'] = 100;
        $fsa[60]['RAP'] = 100; $fsa[61]['RAP'] = 100; $fsa[62]['RAP'] = 100; $fsa[63]['RAP'] = 100; $fsa[64]['RAP'] = 100;
        $fsa[60]['PCL'] = 100; $fsa[61]['PCL'] = 100; $fsa[62]['PCL'] = 100; $fsa[63]['PCL'] = 100; $fsa[64]['PCL'] = 100;
        $fsa[60]['NTE'] = 61;  $fsa[61]['NTE'] = 61;  $fsa[62]['NTE'] = 100; $fsa[63]['NTE'] = 64;  $fsa[64]['NTE'] = 100;
        $fsa[60]['N1']  = 100; $fsa[61]['N1']  = 100; $fsa[62]['N1']  = 100; $fsa[63]['N1']  = 100; $fsa[64]['N1']  = 100;
        $fsa[60]['N2']  = 100; $fsa[61]['N2']  = 100; $fsa[62]['N2']  = 100; $fsa[63]['N2']  = 100; $fsa[64]['N2']  = 100;
        $fsa[60]['N3']  = 100; $fsa[61]['N3']  = 100; $fsa[62]['N3']  = 100; $fsa[63]['N3']  = 100; $fsa[64]['N3']  = 100;
        $fsa[60]['N4']  = 100; $fsa[61]['N4']  = 100; $fsa[62]['N4']  = 100; $fsa[63]['N4']  = 100; $fsa[64]['N4']  = 100;
        $fsa[60]['PER'] = 100; $fsa[61]['PER'] = 100; $fsa[62]['PER'] = 100; $fsa[63]['PER'] = 100; $fsa[64]['PER'] = 100;
        $fsa[60]['IN1'] = 100; $fsa[61]['IN1'] = 100; $fsa[62]['IN1'] = 100; $fsa[63]['IN1'] = 100; $fsa[64]['IN1'] = 100;
        $fsa[60]['IN2'] = 100; $fsa[61]['IN2'] = 100; $fsa[62]['IN2'] = 100; $fsa[63]['IN2'] = 100; $fsa[64]['IN2'] = 100;
        $fsa[60]['SST'] = 100; $fsa[61]['SST'] = 100; $fsa[62]['SST'] = 100; $fsa[63]['SST'] = 100; $fsa[64]['SST'] = 100;
        $fsa[60]['SSE'] = 100; $fsa[61]['SSE'] = 100; $fsa[62]['SSE'] = 100; $fsa[63]['SSE'] = 100; $fsa[64]['SSE'] = 100;
        $fsa[60]['ATV'] = 100; $fsa[61]['ATV'] = 100; $fsa[62]['ATV'] = 100; $fsa[63]['ATV'] = 100; $fsa[64]['ATV'] = 100;
        $fsa[60]['TST'] = 100; $fsa[61]['TST'] = 100; $fsa[62]['TST'] = 100; $fsa[63]['TST'] = 26;  $fsa[64]['TST'] = 100;
        $fsa[60]['SBT'] = 100; $fsa[61]['SBT'] = 100; $fsa[62]['SBT'] = 100; $fsa[63]['SBT'] = 35;  $fsa[64]['SBT'] = 100;
        $fsa[60]['SRE'] = 100; $fsa[61]['SRE'] = 100; $fsa[62]['SRE'] = 100; $fsa[63]['SRE'] = 63;  $fsa[64]['SRE'] = 100;
        $fsa[60]['SUM'] = 100; $fsa[61]['SUM'] = 100; $fsa[62]['SUM'] = 100; $fsa[63]['SUM'] = 27;  $fsa[64]['SUM'] = 100;
        $fsa[60]['LX']  = 100; $fsa[61]['LX']  = 100; $fsa[62]['LX']  = 100; $fsa[63]['LX']  = 28;  $fsa[64]['LX']  = 28;
        $fsa[60]['IMM'] = 100; $fsa[61]['IMM'] = 100; $fsa[62]['IMM'] = 100; $fsa[63]['IMM'] = 100; $fsa[64]['IMM'] = 100;
        $fsa[60]['SES'] = 38;  $fsa[61]['SES'] = 38;  $fsa[62]['SES'] = 38;  $fsa[63]['SES'] = 100; $fsa[64]['SES'] = 100;
        $fsa[60]['CRS'] = 100; $fsa[61]['CRS'] = 100; $fsa[62]['CRS'] = 46;  $fsa[63]['CRS'] = 100; $fsa[64]['CRS'] = 100;
        $fsa[60]['CSU'] = 100; $fsa[61]['CSU'] = 100; $fsa[62]['CSU'] = 100; $fsa[63]['CSU'] = 100; $fsa[64]['CSU'] = 100;
        $fsa[60]['MKS'] = 100; $fsa[61]['MKS'] = 100; $fsa[62]['MKS'] = 57;  $fsa[63]['MKS'] = 100; $fsa[64]['MKS'] = 100;
        $fsa[60]['DEG'] = 47;  $fsa[61]['DEG'] = 47;  $fsa[62]['DEG'] = 47;  $fsa[63]['DEG'] = 100; $fsa[64]['DEG'] = 100;
        $fsa[60]['FOS'] = 100; $fsa[61]['FOS'] = 100; $fsa[62]['FOS'] = 100; $fsa[63]['FOS'] = 100; $fsa[64]['FOS'] = 100;
        $fsa[60]['SE']  = 39;  $fsa[61]['SE']  = 39;  $fsa[62]['SE']  = 39;  $fsa[63]['SE']  = 100; $fsa[64]['SE']  = 100;
        $fsa[60]['GE']  = 100; $fsa[61]['GE']  = 100; $fsa[62]['GE']  = 100; $fsa[63]['GE']  = 100; $fsa[64]['GE']  = 100;
        $fsa[60]['IEA'] = 100; $fsa[61]['IEA'] = 100; $fsa[62]['IEA'] = 100; $fsa[63]['IEA'] = 100; $fsa[64]['IEA'] = 100;

        return $fsa;
    }

    private static function getTokens()
    {
        return array(
            'ISA',
            'GS',
            'ST',
            'BGN',
            'ERP',
            'REF',
            'DMG',
            'LUI',
            'IND',
            'DTP',
            'RAP',
            'PCL',
            'NTE',
            'N1',
            'N2',
            'N3',
            'N4',
            'PER',
            'IN1',
            'IN2',
            'SST',
            'SSE',
            'ATV',
            'TST',
            'SBT',
            'SRE',
            'SUM',
            'LX',
            'IMM',
            'SES',
            'CRS',
            'CSU',
            'MKS',
            'DEG',
            'FOS',
            'SE',
            'GE',
            'IEA',
        );
    }

    public static function writePdf($infile, $outfile)
    {
        if (!file_exists($infile)) {
            throw new \Exception("File not found: $infile");
        }

        $transcript = json_decode(file_get_contents($infile));

        foreach ($transcript->n1s as $institution) {
            if ($institution->n1->entityIdentifierCode == 'AS') {
                $sender = $institution;
            }

            if ($institution->n1->entityIdentifierCode == 'AT') {
                $receiver = $institution;
            }
        }

        $ssn = '';

        foreach ($transcript->refs as $identification) {
            if ($identification->referenceIdQualifier == 'SY') {
                $ssn = '*****' . substr($identification->referenceId, -4);
            }
        }

        $index = 830;
        $pad = 80;

        $pdf = new Zend_Pdf();

        $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
        $page->setFillColor(Zend_Pdf_Color_Html::color('black'));
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER), 8);

        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 10;
        $page->drawText(str_pad("FICE: {$sender->n1->identificationCode}     Sent: {$transcript->bgn->date}", $pad, ' ', STR_PAD_BOTH), 10, $index);
        $index -= 10;
        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 20;

        if (count($transcript->in1s) > 1) {
            foreach ($transcript->in1s as $identification) {
                if ($identification->in1->nameTypeCode == '04') {
                    $applicant = $identification;
                }
            }
        } else {
            $applicant = $transcript->in1s[0];
        }

        $prefix = isset($applicant->in2[0]->prefix) ? $applicant->in2[0]->prefix . ' ' : '';
        $firstName = isset($applicant->in2[0]->firstName) ? $applicant->in2[0]->firstName . ' ' : '';
        $firstMiddleName = isset($applicant->in2[0]->firstMiddleName) ? $applicant->in2[0]->firstMiddleName . ' ' : '';
        $secondMiddleName = isset($applicant->in2[0]->secondMiddleName) ? $applicant->in2[0]->secondMiddleName . ' ' : '';
        $lastName = isset($applicant->in2[0]->lastName) ? $applicant->in2[0]->lastName . ' ' : '';
        $firstInitial = isset($applicant->in2[0]->firstInitial) ? $applicant->in2[0]->firstInitial . ' ' : '';
        $firstMiddleInitial = isset($applicant->in2[0]->firstMiddleInitial) ? $applicant->in2[0]->firstMiddleInitial . ' ' : '';
        $secondMiddleInitial = isset($applicant->in2[0]->secondMiddleInitial) ? $applicant->in2[0]->secondMiddleInitial . ' ' : '';
        $suffix = isset($applicant->in2[0]->suffix) ? $applicant->in2[0]->suffix . ' ' : '';
        $combinedName = isset($applicant->in2[0]->combinedName) ? $applicant->in2[0]->combinedName . ' ' : '';
        $agencyName = isset($applicant->in2[0]->agencyName) ? $applicant->in2[0]->agencyName . ' ' : '';
        $maidenName = isset($applicant->in2[0]->maidenName) ? $applicant->in2[0]->maidenName . ' ' : '';
        $compositeName = isset($applicant->in2[0]->compositeName) ? $applicant->in2[0]->compositeName . ' ' : '';
        $middleNames = isset($applicant->in2[0]->middleNames) ? $applicant->in2[0]->middleNames . ' ' : '';
        $preferredFirstName = isset($applicant->in2[0]->preferredFirstName) ? $applicant->in2[0]->preferredFirstName . ' ' : '';
        $organizationName = isset($applicant->in2[0]->organizationName) ? $applicant->in2[0]->organizationName . ' ' : '';
        $name = isset($applicant->in2[0]->name) ? $applicant->in2[0]->name . ' ' : '';

        $page->drawText(str_pad("   Name: {$prefix}{$firstName}{$firstMiddleName}{$secondMiddleName}{$lastName}{$suffix}", 40, ' ', STR_PAD_RIGHT) . str_pad("Sex: {$transcript->dmg->genderCode}", 40, ' ', STR_PAD_RIGHT), 10, $index);
        $index -= 10;

        $page->drawText(str_pad("   SSN: $ssn", 40, ' ', STR_PAD_RIGHT) . str_pad("Address: {$applicant->n3s[0]->n3->address1}", 40, ' ', STR_PAD_RIGHT), 10, $index);
        $index -= 10;

        $page->drawText(str_pad("   Birthdate ({$transcript->dmg->dateTimeFormatQualifier}): {$transcript->dmg->dateOfBirth}", 40, ' ', STR_PAD_RIGHT) . str_pad("         {$applicant->n3s[0]->n4->city} {$applicant->n3s[0]->n4->state} {$applicant->n3s[0]->n4->postalCode}", 40, ' ', STR_PAD_RIGHT), 10, $index);
        $index -= 10;

        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 10;
        $page->drawText(str_pad("(AS = Sending Institution, AT = Intended Recipient)", $pad, ' ', STR_PAD_BOTH), 10, $index);
        $index -= 10;
        $page->drawText(str_repeat(' ', $pad), 10, $index);
        $index -= 10;
        $page->drawText(str_pad("   RAP:", $pad, ' ', STR_PAD_RIGHT), 10, $index);
        $index -= 10;

        if (isset($transcript->raps)) {
            foreach ($transcript->raps as $rap) {
                $usageIndicator = isset($rap->usageIndicator) ? $rap->usageIndicator : '';
                $requirementMet = isset($rap->requirementMet) ? $rap->requirementMet : '';
                $dateTimePeriod = isset($rap->dateTimePeriod) ? $rap->dateTimePeriod : '';

                $line = str_pad($rap->requirementCode, 5, ' ', STR_PAD_RIGHT);
                $line .= str_pad($rap->mainCategory, 15, ' ', STR_PAD_RIGHT);
                $line .= str_pad($rap->lesserCategory, 45, ' ', STR_PAD_RIGHT);
                $line .= str_pad($usageIndicator, 1, ' ', STR_PAD_RIGHT);
                $line .= str_pad($requirementMet, 2, ' ', STR_PAD_RIGHT);
                $line .= str_pad($dateTimePeriod, 8, ' ', STR_PAD_RIGHT);

                $page->drawText(str_pad("$line", $pad, ' ', STR_PAD_LEFT), 10, $index);
                $index -= 10;
            }

            $index -= 20;
        }

        $page->drawText("   AS: {$sender->n1->name}", 10, $index);
        $index -= 10;
        if (isset($sender->n3)) {
            $page->drawText("       {$sender->n3->address01}", 10, $index);
        }
        $index -= 10;
        if (isset($sender->n4)) {
            $page->drawText("       {$sender->n4->cityName}, {$sender->n4->stateCode} {$sender->n4->postalCode}", 10, $index);
            $index -= 10;
        }

        $index -= 10;

        $page->drawText("   AT: {$receiver->n1->name}", 10, $index);
        $index -= 10;
        if (isset($receiver->n3)) {
            $page->drawText("       {$receiver->n3->address01}", 10, $index);
        }
        $index -= 10;
        if (isset($receiver->n4)) {
            $page->drawText("       {$receiver->n4->cityName}, {$receiver->n4->stateCode} {$receiver->n4->postalCode}", 10, $index);
            $index -= 10;
        }

        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 10;

        if (isset($transcript->pcls)) {
            foreach ($transcript->pcls as $previous) {
                $page->drawText("   Previous College: {$previous->description} ({$previous->identificationCode})", 10, $index);
                $index -= 10;
                $page->drawText("       Attend Dates: {$previous->datesAttended}", 10, $index);
                $index -= 10;
                $page->drawText("       Degree: {$previous->academicDegreeCode}", 10, $index);
                $index -= 10;
                $page->drawText("       Degree Date: {$previous->dateDegreeConferred}", 10, $index);
                $index -= 20;
            }
        }

        if (isset($transcript->ssts)) {
            foreach ($transcript->ssts as $sst) {
                if (isset($sst->n1)) {
                    $page->drawText("   HS: {$sst->n1->name}", 10, $index);
                    $index -= 10;
                    $page->drawText("       {$sst->n4->city}, {$sst->n4->state}", 10, $index);
                    $index -= 20;
                    $page->drawText("       Graduation Date: ({$sst->sst->highSchoolGraduationDateFormat}) {$sst->sst->highSchoolGraduationDate}", 10, $index);
                    $index -= 10;
                    $page->drawText("       Graduation Type: {$sst->sst->highSchoolGraduationType}", 10, $index);
                    $index -= 20;
                }
            }
        }

        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 10;

        if (isset($transcript->sums)) {
            foreach ($transcript->sums as $summary) {
                $page->drawText(str_pad("     Credit Type: {$summary->sum->creditTypeCode}", 40, ' ', STR_PAD_RIGHT) . str_pad("Included Hours: {$summary->sum->creditHoursIncluded}", 40, ' ', STR_PAD_RIGHT), 10, $index);
                $index -= 10;
                $page->drawText(str_pad("     Level: {$summary->sum->gradeOrCourseLevelCode}", 40, ' ', STR_PAD_RIGHT) . str_pad("Quality Points: {$summary->sum->qualityPointsUsedToCalculateGpa}", 40, ' ', STR_PAD_RIGHT), 10, $index);
                $index -= 10;
                $page->drawText(str_pad("     Cumulative: {$summary->sum->cumulativeSummaryIndicator}", 40, ' ', STR_PAD_RIGHT) . str_pad("GPA: {$summary->sum->gradePointAverage}", 40, ' ', STR_PAD_RIGHT), 10, $index);
                $index -= 20;
            }
        }

        if (isset($transcript->detail->degs)) {
            foreach ($transcript->detail->degs as $degree) {
                $page->drawText(str_pad("Academic Degree ({$degree->deg->degreeCode}): {$degree->deg->title}", $pad, ' ', STR_PAD_BOTH), 10, $index);
                $index -= 10;
                $page->drawText(str_pad("Date Awarded ({$degree->deg->degreeAwardedDateFormat}): {$degree->deg->degreeAwardedDate}", $pad, ' ', STR_PAD_BOTH), 10, $index);
                $index -= 10;
            }
        }

        $page->drawText(str_repeat('-', $pad), 10, $index);
        $index -= 10;

        $holder = array();

        // Need to order sessions by date
        foreach ($transcript->detail->sess as $session) {
            $holder[$session->ses->startDate] = $session;
        }

        ksort($holder);
        reset($holder);

        $tempLines = array();
        $tempIndex = array();

        foreach ($holder as $session) {
            if ($session->crss[0]->crs->basisForCredit == 'T') {
                $tempLines[] = str_pad("** TRANSFER CREDIT **", $pad, ' ', STR_PAD_BOTH);
                $tempIndex[] = 10;
            }

            $tempLines[] = str_pad("{$session->ses->name} {$session->ses->startDate} - {$session->ses->endDate} {$session->ses->curriculumName}", $pad, ' ', STR_PAD_BOTH);
            $tempIndex[] = 10;

            $type = $session->sums[0]->sum->creditTypeCode;
            $code = $session->sums[0]->sum->gradeOrCourseLevelCode;
            $attempted = $session->sums[0]->sum->creditHoursAttempted;
            $earned = $session->sums[0]->sum->creditHoursEarned;
            $gpa = $session->sums[0]->sum->gradePointAverage;
            $cum = $session->sums[0]->sum->cumulativeSummaryIndicator;
            $level = $session->ses->gradeLevel;

            $line = "Cred Typ: $type  Levl: $code  Ahrs: $attempted  Ehrs: $earned  GPA: $gpa  Lvl: $level  Cum: $cum";

            $tempLines[] = $line;
            $tempIndex[] = 20;

            $tempLines[] = str_pad("Rpt", $pad, ' ', STR_PAD_LEFT);
            $tempIndex[] = 10;

            if (is_array($session->crss)) {
                foreach ($session->crss as $course) {
                    switch ($course->crs->courseRepeat) {
                        case 'N':
                            $repeat = 'E';
                            break;
                        case 'R':
                            $repeat = 'I';
                            break;
                        default:
                            $repeat = $course->crs->courseRepeat;
                            break;
                    }
                    $tempLines[] = str_pad("   {$course->crs->courseSubjectAbbreviation}    {$course->crs->courseNumber} {$course->crs->courseTitle}", 60, ' ', STR_PAD_RIGHT) . str_pad($course->crs->creditHoursEarned, 5, STR_PAD_LEFT) . " {$course->crs->grade}          $repeat ";
                    $tempIndex[] = 10;

                    if (isset($course->raps)) {
                        foreach ($course->raps as $rap) {
                            $tempLines[] = "     RAP: {$rap->courseRequirement} {$rap->mainCategoryOfRequirement} {$rap->lesserCategoryOfRequirement} ({$rap->usageIndicator}{$rap->requirementMet})";
                            $tempIndex[] = 10;
                        }
                    }
                }

                $tempLines[] = str_repeat('-', $pad);
                $tempIndex[] = 10;
            }

            // don't overflow the end of the page
            if ($index - array_sum($tempIndex) <= 0) {
                $pdf->pages[] = $page;

                $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
                $page->setFillColor(Zend_Pdf_Color_Html::color('black'));
                $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER), 8);

                $index = 800;
            }

            for ($i=0; $i<count($tempLines); $i++) {
                $page->drawText($tempLines[$i], 10, $index);
                $index -= $tempIndex[$i];
            }

            $tempLines = array();
            $tempIndex = array();
        }

        $pdf->pages[] = $page;
        $out = $pdf->render();

        if (!file_put_contents($outfile, $out)) {
            throw new \Exception("Not writable: $outfile");
        }
    }

    public static function writeXml($infile, $outfile)
    {
        if (!file_exists($infile)) {
            throw new \Exception("File not found: $infile");
        }

        $transcript = json_decode(file_get_contents($infile));

        $serializer = SerializerBuilder::create()->build();

        $xml = $serializer->serialize($transcript, 'xml');

        if (!file_put_contents($outfile, $xml)) {
            throw new \Exception("Not writable: $outfile");
        }
    }
}

