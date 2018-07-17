<?php
namespace Shedi;

class Application
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

        // holders
        $application = null;
        $institution = null;
        $referenceIdentification = null;
        $individualIdentification = null;
        $name = null;
        $address = null;
        $communication = null;
        $orgName = null;
        $employmentPosition = null;
        $activities = null;
        $amount = null;
        $entryExit = null;
        $residency = null;
        $request = null;
        $academicStatus = null;
        $sessionHeader = null;
        $courseRecord = null;
        $testScore = null;
        $subTest = null;
        $previousCollege = null;
        $degreeRecord = null;
        $assignedNumber = null;
        $letterOfRec = null;

        $lines = file($infile);

        $lineNumber = 1;

        $tokens = self::getTokens();
        $fsa    = self::getStateTable();

        foreach ($lines as $line) {
            $delimiter = '|';

            $line = trim($line);
            list($token, $rest) = explode($delimiter, $line, 2);

            if (!in_array($token, $tokens)) {
                trigger_error("Unknown Token [$token]");
                exit(1);
            }

            $rest = explode($delimiter, $rest);

            $nextState = $fsa[$currentState][$token];

            switch ($nextState) {
                case 0:
                    // ESA
                    $startOver = true;
                    break;
                case 1:
                    // ISA
                    // ignoring the ISA for now, no docs
                    break;
                case 2:
                    // GS
                    // ignoring the GS for now, no docs
                    break;
                case 3:
                    // ST
                    $application = new \stdClass();
                    $application->st = new \stdClass();
                    $application->st->identifierCode = $rest[0];
                    $application->st->controlNumber = $rest[1];
                    break;
                case 4:
                    // BGN
                    $application->bgn = new \stdClass();
                    $application->bgn->purposeCode = $rest[0];
                    $application->bgn->referenceIdentification = $rest[1];
                    $application->bgn->date = $rest[2];
                    if (isset($rest[3])) {
                        $application->bgn->time = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $application->bgn->timeCode = $rest[4];
                    }
                    break;
                case 5:
                    // N1
                    if (is_object($institution)) {
                        $application->n1s[] = $institution;
                        $institution = null;
                    }

                    $institution = new \stdClass();
                    $institution->n1 = new \stdClass();
                    $institution->n1->entityIdentifierCode = $rest[0];
                    if (isset($rest[1])) {
                        $institution->n1->name = $rest[1];
                    }
                    $institution->n1->codeQualifier = $rest[2];
                    $institution->n1->code = $rest[3];
                    break;
                case 6:
                    // N1-N2
                    $institution->n2 = new \stdClass();
                    $institution->n2->name01 = $rest[0];
                    if (isset($rest[1])) {
                        $institution->n2->name02 = $rest[1];
                    }
                    break;
                case 7:
                    // N1-N3
                    $institution->n3 = new \stdClass();
                    $institution->n3->address01 = $rest[0];
                    if (isset($rest[1])) {
                        $institution->n3->address02 = $rest[1];
                    }
                    break;
                case 8:
                    // N1-N4
                    $institution->n4 = new \stdClass();
                    $institution->n4->cityName = $rest[0];
                    $institution->n4->stateCode = $rest[1];
                    $institution->n4->postalCode = $rest[2];
                    if (isset($rest[3])) {
                        $institution->n4->countryCode = $rest[3];
                    }
                    break;
                case 9:
                    // N1-PER
                    $institution->per = new \stdClass();
                    $institution->per->contactFunctionCode = $rest[0];
                    if (isset($rest[1])) {
                        $institution->per->name = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $institution->per->communicationNumberQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $institution->per->communicationNumber = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $institution->per->communicationNumberQualifier2 = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $institution->per->communicationNumber = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $institution->per->communicationNumberQualifier3 = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $institution->per->communicationNumber3 = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $institution->per->contactInquiryReference = $rest[8];
                    }
                    break;
                case 10:
                    // REF
                    if (is_object($referenceIdentification)) {
                        $application->refs[] = $referenceIdentification;
                        $referenceIdentification = null;
                    }
                    $referenceIdentification = new \stdClass();
                    $referenceIdentification->ref = new \stdClass();
                    $referenceIdentification->ref->identificationQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $referenceIdentification->ref->identification = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $referenceIdentification->ref->description = $rest[2];
                    }
                    break;
                case 11:
                    // REF-DTP
                    $referenceIdentification->dtp = new \stdClass();
                    $referenceIdentification->dtp->qualifier = $rest[0];
                    $referenceIdentification->dtp->formatQualifier = $rest[1];
                    $referenceIdentification->dtp->date = $rest[2];
                    break;
                case 12:
                    // REF-N4
                    $referenceIdentification->n4 = new \stdClass();
                    $referenceIdentification->n4->city = $rest[0];
                    $referenceIdentification->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $referenceIdentification->n4->postalCode = $rest[2];
                    }
                    $referenceIdentification->n4->countryCode = $rest[3];
                    break;
                case 13:
                    // REF-N1
                    $referenceIdentification->n1->identifierCode = $rest[0];
                    $referenceIdentification->n1->name = $rest[1];
                    $referenceIdentification->n1->codeQualifier = $rest[2];
                    $referenceIdentification->n1->code = $rest[3];
                    break;
                case 14:
                    // IN1
                    if (is_object($referenceIdentification)) {
                        $application->refs[] = $referenceIdentification;
                        $referenceIdentification = null;
                    }

                    if (is_object($individualIdentification)) {
                        $application->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }
                    $individualIdentification = new \stdClass();
                    $individualIdentification->in1 = new \stdClass();
                    $individualIdentification->in1->entityTypeQualifier = $rest[0];
                    $individualIdentification->in1->nameTypeCode = $rest[1];
                    $individualIdentification->in1->relationshipCode = $rest[5];
                    break;
                case 15:
                    // IN1-IN2
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
                case 16:
                    // IN1-REF
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->identificationQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->identification = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->description = $rest[2];
                    }

                    $individualIdentification->refs[] = $tmp;
                    $tmp = null;
                    break;
                case 17:
                    // IN1-DMG
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    switch ($rest[0]) {
                        case 'CM':
                            $tmp->formatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $tmp->formatQualifier = 'CCYYMMDD';
                            break;
                        case 'YY':
                            $tmp->formatQualifier = 'YY';
                            break;
                        case 'MD':
                            $tmp->formatQualifier = 'MMDD';
                            break;
                    }
                    $tmp->dateOfBirth = $rest[1];
                    if (isset($rest[2])) {
                        $tmp->genderCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->maritalStatusCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $tmp->raceEthnicityCode = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $tmp->citizenshipCode = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $tmp->countryCode = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $tmp->verificationCode = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $tmp->age = $rest[8];
                    }

                    $individualIdentification->dmg = $tmp;
                    $tmp = null;
                    break;
                case 18:
                    // IN1-IND
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->countryCode = $rest[0];
                    $tmp->state = $rest[1];
                    $tmp->county = $rest[2];
                    $tmp->city = $rest[3];

                    $individualIdentification->ind = $tmp;
                    $tmp = null;
                    break;
                case 19:
                    // IN1-IMM
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->industryCode = $rest[0];
                    switch ($rest[1]) {
                        case 'CC':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'CM':
                            $tmp->formatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $tmp->formatQualifier = 'CCYYMMDD';
                            break;
                        case 'DB':
                            $tmp->formatQualifier = 'MMDDCCYY';
                            break;
                        case 'YY':
                            $tmp->formatQualifier = 'YY';
                            break;
                    }
                    $tmp->date = $rest[2];
                    $tmp->immunizationStatusCode = $rest[3];
                    $tmp->reportTypeCode = $rest[4];
                    $tmp->codeListQualifierCode = $rest[5];

                    $individualIdentification->imms[] = $tmp;
                    $tmp = null;
                    break;
                case 20:
                    // IN1-LUI
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

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

                    $individualIdentification->luis[] = $tmp;
                    $tmp = null;
                    break;
                case 21:
                    // IN1-III
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->qualifierCode = $rest[0];
                    $tmp->industryCode = $rest[1];
                    if (isset($rest[3])) {
                        $tmp->message = $rest[3];
                    }

                    $individualIdentification->iiis[] = $tmp;
                    $tmp = null;
                    break;
                case 22:
                    // IN1-NTE
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    $tmp = new \stdClass();
                    $tmp->referenceCode = $rest[0];
                    $tmp->description = $rest[1];

                    $individualIdentification->ntes[] = $tmp;
                    $tmp = null;
                    break;
                case 23:
                    // IN1-N3
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
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
                case 24:
                    // IN1-COM
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    if (is_object($communication)) {
                        $individualIdentification->coms[] = $communication;
                        $communication = null;
                    }

                    $communication = new \stdClass();
                    $communication->com = new \stdClass();
                    $communication->com->qualiferCode = $rest[0];
                    switch ($rest[0]) {
                        case 'AP':
                            $communication->qualifier = "Alternate Telephone AS Answering Service BN Beeper Number";
                            break;
                        case 'CP':
                            $communication->qualifier = "Cellular Phone";
                            break;
                        case 'EM':
                            $communication->qualifier = "Electronic Mail";
                            break;
                        case 'EX':
                            $communication->qualifier = "Telephone Extension";
                            break;
                        case 'FX':
                            $communication->qualifier = "Facsimile";
                            break;
                        case 'HF':
                            $communication->qualifier = "Home Facsimile Number";
                            break;
                        case 'HP':
                            $communication->qualifier = "Home Phone Number";
                            break;
                        case 'IT':
                            $communication->qualifier = "International Telephone";
                            break;
                        case 'NP':
                            $communication->qualifier = "Night Telephone";
                            break;
                        case 'OF':
                            $communication->qualifier = "Other Residential Facsimile Number OT Other Residential Telephone Number PC Personal Cellular";
                            break;
                        case 'PP':
                            $communication->qualifier = "Personal Phone";
                            break;
                        case 'TE':
                            $communication->qualifier = "Telephone";
                            break;
                        case 'UR':
                            $communication->qualifier = "Uniform Resource Locator (URL) VM Voice Mail";
                            break;
                        case 'WC':
                            $communication->qualifier = "Work Cellular";
                            break;
                        case 'WF':
                            $communication->qualifier = "Work Facsimile Number";
                            break;
                        case 'WP':
                            $communication->qualifier = "Work Phone Number";
                            break;
                    }
                    $communication->com->number = $rest[1];
                    break;
                case 25:
                    // IN1-N1
                    if (is_object($name)) {
                        $individualIdentification->in2 = $name;
                        $name = null;
                    }

                    if (is_object($orgName)) {
                        $individualIdentification->n1s[] = $orgName;
                        $orgName = null;
                    }

                    $orgName = new \stdClass();
                    $orgName->n1 = new \stdClass();
                    $orgName->n1->identifierCode = $rest[0];
                    $orgName->n1->name = $rest[1];
                    break;
                case 26:
                    // ATV
                    if (is_object($individualIdentification)) {
                        if (is_object($name)) {
                            $individualIdentification->in2 = $name;
                            $name = null;
                        }

                        if (is_object($address)) {
                            $individualIdentification->n3s[] = $address;
                            $address = null;
                        }

                        if (is_object($communication)) {
                            $individualIdentification->coms[] = $communication;
                            $communication = null;
                        }

                        if (is_object($employmentPosition)) {
                            $orgName->emss[] = $employmentPosition;
                            $employmentPosition = null;
                        }

                        if (is_object($orgName)) {
                            $individualIdentification->n1s[] = $orgName;
                            $orgName = null;
                        }

                        $application->in1s[] = $individualIdentification;
                        $individualIdentification = null;
                    }

                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    $activities = new \stdClass();
                    $activities->atv = new \stdClass();
                    $activities->atv->qualifierCode = $rest[0];
                    $activities->atv->industryCode = $rest[1];
                    $activities->atv->entityTitle = $rest[2];
                    $activities->atv->entityTitle2 = $rest[3];
                    $activities->atv->quantity = $rest[4];
                    $activities->atv->unitOfMeasure = $rest[5];
                    // snip
                    break;
                case 27:
                    // AMT
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    $amount = new \stdClass();
                    $amount->amt->qualifierCode = $rest[0];
                    $amount->amt->amount = $rest[1];
                    break;
                case 28:
                    // SSE
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    $entryExit = new \stdClass();
                    $entryExit->sse = new \stdClass();
                    $entryExit->sse->date = $rest[0];
                    if (isset($rest[3])) {
                        $entryExit->sse->number = $rest[3];
                    }
                    break;
                case 29:
                    // RSD
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    $residency = new \stdClass();
                    $residency->rsd = new \stdClass();
                    $residency->rsd->qualifierCode = $rest[0];
                    if (isset($rest[1])) {
                        $residency->rsd->industryCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $residency->rsd->relationshipCode = $rest[2];
                    }
                    break;
                case 30:
                    // RQS
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    $request = new \stdClass();
                    $request->rqs = new \stdClass();
                    $request->rqs->qualifierCode = $rest[0];
                    if (isset($rest[1])) {
                        $request->rqs->industryCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $request->rqs->description = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $request->rqs->responseCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $request->rqs->description2 = $rest[4];
                    }
                    break;
                case 31:
                    // SST
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
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
                case 32:
                    // TST
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            $academicStatus->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($testScore)) {
                        if (is_object($subTest)) {
                            $testScore->sbts[] = $subTest;
                            $subTest = null;
                        }

                        $application->tsts[] = $testScore;
                        $testScore = null;
                    }

                    $testScore = new \stdClass();
                    $testScore->tst = new \stdClass();
                    $testScore->tst->testCode = $rest[0];
                    if (isset($rest[1])) {
                        $testScore->tst->testName = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $testScore->tst->testAdministeredDateFormat = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $testScore->tst->testAdministeredDate = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $testScore->tst->testForm = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $testScore->tst->testLevel = $rest[5];
                    }
                    if (isset($rest[6])) {
                        $testScore->tst->studentGradeLevel = $rest[6];
                    }
                    break;
                case 33:
                    // PCL
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            $academicStatus->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($testScore)) {
                        if (is_object($subTest)) {
                            $testScore->sbts[] = $subTest;
                            $subTest = null;
                        }

                        $application->tsts[] = $testScore;
                        $testScore = null;
                    }

                    if (is_object($previousCollege)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            if (is_object($degreeRecord)) {
                                $sessionHeader->degs[] = $degreeRecord;
                                $degreeRecord = null;
                            }

                            $previousCollege->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->pcls[] = $previousCollege;
                        $previousCollege = null;
                    }

                    $previousCollege = new \stdClass();
                    $previousCollege->pcl = new \stdClass();
                    if (isset($rest[0])) {
                        $previousCollege->pcl->identificationCodeQualifier = $rest[0];
                    }
                    if (isset($rest[1])) {
                        $previousCollege->pcl->identificationCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $previousCollege->pcl->dateTimePeriodFormatQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $previousCollege->pcl->datesAttended = $rest[3];
                    }
                    if (isset($rest[6])) {
                        $previousCollege->pcl->description = $rest[6];
                    }
                    break;
                case 34:
                    // LX
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            $academicStatus->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($testScore)) {
                        if (is_object($subTest)) {
                            $testScore->sbts[] = $subTest;
                            $subTest = null;
                        }

                        $application->tsts[] = $testScore;
                        $testScore = null;
                    }

                    if (is_object($previousCollege)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            if (is_object($degreeRecord)) {
                                $sessionHeader->degs[] = $degreeRecord;
                                $degreeRecord = null;
                            }

                            $previousCollege->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->pcls[] = $previousCollege;
                        $previousCollege = null;
                    }

                    if (is_object($assignedNumber)) {
                        $application->lx = $assignedNumber;
                        $assignedNumber = null;
                    }

                    $assignedNumber = new \stdClass();
                    $assignedNumber->lx = new \stdClass();
                    $assignedNumber->lx->assignedNumber = $rest[0];
                    break;
                case 35:
                    // LT
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            $academicStatus->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($testScore)) {
                        if (is_object($subTest)) {
                            $testScore->sbts[] = $subTest;
                            $subTest = null;
                        }

                        $application->tsts[] = $testScore;
                        $testScore = null;
                    }

                    if (is_object($previousCollege)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            if (is_object($degreeRecord)) {
                                $sessionHeader->degs[] = $degreeRecord;
                                $degreeRecord = null;
                            }

                            $previousCollege->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->pcls[] = $previousCollege;
                        $previousCollege = null;
                    }

                    if (is_object($assignedNumber)) {
                        $application->lx = $assignedNumber;
                        $assignedNumber = null;
                    }

                    if (is_object($letterOfRec)) {
                        $application->lts[] = $letterOfRec;
                        $letterOfRec = null;
                    }

                    $letterOfRec = new \stdClass();
                    $letterOfRec->lt = new \stdClass();
                    $letterOfRec->lt->relationshipCode = $rest[0];
                    $letterOfRec->lt->recommendationType = $rest[1];
                    $letterOfRec->lt->name = $rest[2];
                    $letterOfRec->lt->recommendersTitle = $rest[3];
                    break;
                case 36:
                    // SE
                    if (is_object($activities)) {
                        $application->atvs[] = $activities;
                        $activities = null;
                    }

                    if (is_object($amount)) {
                        $application->amts[] = $amount;
                        $amount = null;
                    }

                    if (is_object($entryExit)) {
                        $application->sses[] = $entryExit;
                        $entryExit = null;
                    }

                    if (is_object($residency)) {
                        $application->rsds[] = $residency;
                        $residency = null;
                    }

                    if (is_object($request)) {
                        $application->rqss[] = $request;
                        $request = null;
                    }

                    if (is_object($academicStatus)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            $academicStatus->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->ssts[] = $academicStatus;
                        $academicStatus = null;
                    }

                    if (is_object($testScore)) {
                        if (is_object($subTest)) {
                            $testScore->sbts[] = $subTest;
                            $subTest = null;
                        }

                        $application->tsts[] = $testScore;
                        $testScore = null;
                    }

                    if (is_object($previousCollege)) {
                        if (is_object($sessionHeader)) {
                            if (is_object($courseRecord)) {
                                $sessionHeader->crss[] = $courseRecord;
                                $courseRecord = null;
                            }

                            if (is_object($degreeRecord)) {
                                $sessionHeader->degs[] = $degreeRecord;
                                $degreeRecord = null;
                            }

                            $previousCollege->sess[] = $sessionHeader;
                            $sessionHeader = null;
                        }

                        $application->pcls[] = $previousCollege;
                        $previousCollege = null;
                    }

                    if (is_object($assignedNumber)) {
                        $application->lx = $assignedNumber;
                        $assignedNumber = null;
                    }

                    if (is_object($letterOfRec)) {
                        $application->lts[] = $letterOfRec;
                        $letterOfRec = null;
                    }

                    foreach ($application->refs as $ref) {
                        if ($ref->ref->identificationQualifier == '48') {
                            $id = $ref->ref->identification;
                        }
                    }

                    if (!$id) {
                        error_log("Could not find ApplyTexasID in application");

                        throw new \Exception("Could not find ApplyTexasID in application");
                    }

                    $output = json_encode($application);

                    file_put_contents("{$outdir}/{$id}.json", $output);
                    break;
                case 37:
                    // IN1-N3-N4
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
                    if (isset($rest[5])) {
                        $address->n4->locationIdentifier = $rest[5];
                    }
                    break;
                case 38:
                    // IN1-N3-DTP
                    $tmp = new \stdClass();
                    $tmp->qualifer = $rest[0];
                    switch ($rest[1]) {
                        case 'CM':
                            $tmp->formatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $tmp->formatQualifier = 'CCYYMMDD';
                            break;
                    }
                    $tmp->date = $rest[2];

                    $address->dtps[] = $tmp;
                    $tmp = null;
                    break;
                case 39:
                    // IN1-COM-DTP
                    $tmp = new \stdClass();
                    $tmp->qualifer = $rest[0];
                    switch ($rest[1]) {
                        case 'CM':
                            $tmp->formatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $tmp->formatQualifier = 'CCYYMMDD';
                            break;
                    }
                    $tmp->date = $rest[2];

                    $communication->dtps[] = $tmp;
                    break;
                case 40:
                    // IN1-N1-N3
                    $orgName->n3 = new \stdClass();
                    $orgName->n3->address01 = $rest[0];
                    $orgName->n3->address02 = $rest[1];
                    break;
                case 41:
                    // IN1-N1-N4
                    $orgName->n4 = new \stdClass();
                    $orgName->n4->city = $rest[0];
                    $orgName->n4->state = $rest[1];
                    $orgName->n4->postalCode = $rest[2];
                    if (isset($rest[3])) {
                        $orgName->n4->countryCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $orgName->n4->locationQualifier = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $orgName->n4->locationIdentifier = $rest[5];
                    }
                    break;
                case 42:
                    // IN1-N1-EMS
                    if (is_object($employmentPosition)) {
                        $orgName->emss[] = $employmentPosition;
                        $employmentPosition = null;
                    }

                    $employmentPosition = new \stdClass();
                    $employmentPosition->ems = new \stdClass();
                    $employmentPosition->ems->description = $rest[0];
                    if (isset($rest[1])) {
                        $employmentPosition->ems->classCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $employmentPosition->ems->occupationCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $employmentPosition->ems->statusCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $employmentPosition->ems->identificationQualifier = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $employmentPosition->ems->identification = $rest[5];
                    }
                    break;
                case 43:
                    // ATV-DTP
                    $tmp = new \stdClass();
                    $tmp->qualifer = $rest[0];
                    switch ($rest[1]) {
                        case 'CM':
                            $tmp->formatQualifier = 'CCYYMM';
                            break;
                        case 'CY':
                            $tmp->formatQualifier = 'CCYY';
                            break;
                        case 'D8':
                            $tmp->formatQualifier = 'CCYYMMDD';
                            break;
                    }
                    $tmp->date = $rest[2];

                    $activities->dtps[] = $tmp;
                    $tmp = null;
                    break;
                case 44:
                    // AMT-MSG
                    $amount->msg = new \stdClass();
                    $amount->msg->text = $rest[0];
                    $amount->msg->controlCode = $rest[1];
                    $amount->msg->number = $rest[2];
                    break;
                case 45:
                    // SSE-DEG
                    $entryExit->deg = new \stdClass();
                    $entryExit->deg->degreeCode = $rest[0];
                    if (isset($rest[3])) {
                        $entryExit->deg->description = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $entryExit->deg->reasonCode = $rest[4];
                    }
                    break;
                case 46:
                    // SSE-FOS
                    $tmp = new \stdClass();
                    $tmp->typeCode = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->identificationCodeQualifier = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->identificationCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->description = $rest[3];
                    }
                    $entryExit->foss[] = $tmp;
                    $tmp = null;
                    break;
                case 47:
                    // RSD-N4
                    if (!is_object($residency)) {
                        $residency = new \stdClass();
                    }
                    $residency->n4 = new \stdClass();
                    $residency->n4->city = $rest[0];
                    $residency->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $residency->n4->postalCode = $rest[2];
                    }
                    $residency->n4->countryCode = $rest[3];
                    break;
                case 48:
                    // RSD-DTP
                    $tmp = new \stdClass();
                    $tmp->qualifier = $rest[0];
                    $tmp->formatQualifier = $rest[1];
                    $tmp->dateTime = $rest[2];

                    $residency->dtps[] = $tmp;
                    $tmp = null;
                    break;
                case 49:
                    // RSD-QTY
                    $residency->qty = new \stdClass();
                    $residency->qty->quantityQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $residency->qty->quantity = $rest[1];
                    }
                    break;
                case 50:
                    // RSD-REF
                    $residency->ref = new \stdClass();
                    $residency->ref->identificationQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $residency->ref->identification = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $residency->ref->description = $rest[2];
                    }
                    break;
                case 51:
                    // RQS-MSG
                    $tmp = new \stdClass();
                    $tmp->messageText = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->controlCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->number = $rest[2];
                    }

                    $request->msgs[] = $tmp;
                    $tmp = null;
                    break;
                case 52:
                    // SST-SSE
                    $academicStatus->sse = new \stdClass();
                    $academicStatus->sse->entryDate = $rest[0];
                    $academicStatus->sse->exitDate = $rest[1];
                    if (isset($rest[2])) {
                        $academicStatus->sse->reasonCode = $rest[2];
                    }
                    break;
                case 53:
                    // SST-N1
                    $tmp = new \stdClass();
                    $tmp->entityIdentifierCode = $rest[0];
                    $tmp->name = $rest[1];
                    if (isset($rest[2])) {
                        $tmp->institutionCodeQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->institutionCode = $rest[3];
                    }

                    $academicStatus->n1s[] = $tmp;
                    $tmp = null;
                    break;
                case 54:
                    // SST-N3
                    $academicStatus->n3 = new \stdClass();
                    $academicStatus->n3->address1 = $rest[0];
                    if (isset($rest[1])) {
                        $academicStatus->n3->address2 = $rest[1];
                    }
                    break;
                case 55:
                    // SST-N4
                    $academicStatus->n4 = new \stdClass();
                    $academicStatus->n4->city = $rest[0];
                    $academicStatus->n4->state = $rest[1];
                    if (isset($rest[2])) {
                        $academicStatus->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $academicStatus->n4->countryCode = $rest[3];
                    }
                    break;
                case 56:
                    // SST-SUM
                    $tmp = new \stdClass();
                    $tmp->creditTypeCode = $rest[0];
                    $tmp->gradeOrCourseLevelCode = $rest[1];
                    $tmp->cumulativeSummaryIndicator = $rest[2];
                    $tmp->creditHoursIncluded = $rest[3];
                    $tmp->creditHoursAttempted = $rest[4];
                    $tmp->creditHoursEarned = $rest[5];
                    if (isset($rest[6])) {
                        $tmp->lowestPossibleGradePointAverage = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $tmp->highestPossibleGradePointAverage = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $tmp->gradePointAverage = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $tmp->excessiveGpaIndicator = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $tmp->classRank = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $tmp->quantity = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $tmp->dateTimePeriodFormatQualifier = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $tmp->dateTimePeriod = $rest[13];
                    }
                    if (isset($rest[14])) {
                        $tmp->daysAttended = $rest[14];
                    }
                    if (isset($rest[15])) {
                        $tmp->daysAbsent = $rest[15];
                    }
                    if (isset($rest[16])) {
                        $tmp->qualityPointsUsedToCalculateGpa = $rest[16];
                    }

                    $academicStatus->sums[] = $tmp;
                    $tmp = null;
                    break;
                case 57:
                    // SST-SES
                    if (is_object($sessionHeader)) {
                        $academicStatus->sess[] = $sessionHeader;
                        $sessionHeader = null;
                    }

                    $sessionHeader = new \stdClass();
                    $sessionHeader->ses = new \stdClass();
                    $sessionHeader->ses->startDate = $rest[0];
                    if (isset($rest[1])) {
                        $sessionHeader->ses->count = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $sessionHeader->ses->schoolYear = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $sessionHeader->ses->sessionCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $sessionHeader->ses->name = $rest[4];
                    }
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
                case 58:
                    // TST-SBT
                    if (is_object($subTest)) {
                        $testScore->sbts[] = $subTest;
                        $subTest = null;
                    }

                    $subTest = new \stdClass();
                    $subTest->sbt = new \stdClass();
                    $subTest->sbt->code = $rest[0];
                    if (isset($rest[1])) {
                        $subTest->sbt->name = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $subTest->sbt->interpretationCode = $rest[2];
                    }
                    break;
                case 59:
                    // PCL-N3
                    $previousCollege->n3 = new \stdClass();
                    $previousCollege->n3->address01 = $rest[0];
                    if (isset($rest[1])) {
                        $previousCollege->n3->address02 = $rest[1];
                    }
                    break;
                case 60:
                    // PCL-N4
                    $previousCollege->n4 = new \stdClass();
                    if (isset($rest[0])) {
                        $previousCollege->n4->city = $rest[0];
                    }
                    if (isset($rest[1])) {
                        $previousCollege->n4->state = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $previousCollege->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $previousCollege->n4->countryCode = $rest[3];
                    }
                    break;
                case 61:
                    // PCL-SSE
                    $previousCollege->sse = new \stdClass();
                    if (isset($rest[0])) {
                        $previousCollege->sse->entryDate = $rest[0];
                    }
                    if (isset($rest[1])) {
                        $previousCollege->sse->exitDate = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $previousCollege->sse->reasonCode = $rest[2];
                    }
                    break;
                case 62:
                    // PCL-SUM
                    $previousCollege->sum = new \stdClass();
                    $previousCollege->sum->creditTypeCode = $rest[0];
                    $previousCollege->sum->gradeOrCourseLevelCode = $rest[1];
                    $previousCollege->sum->cumulativeSummaryIndicator = $rest[2];
                    $previousCollege->sum->creditHoursIncluded = $rest[3];
                    $previousCollege->sum->creditHoursAttempted = $rest[4];
                    $previousCollege->sum->creditHoursEarned = $rest[5];
                    if (isset($rest[6])) {
                        $previousCollege->sum->lowestPossibleGradePointAverage = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $previousCollege->sum->highestPossibleGradePointAverage = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $previousCollege->sum->gradePointAverage = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $previousCollege->sum->excessiveGpaIndicator = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $previousCollege->sum->classRank = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $previousCollege->sum->classSize = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $previousCollege->sum->classRankDateFormatQualifier = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $previousCollege->sum->classRankDate = $rest[13];
                    }
                    if (isset($rest[16])) {
                        $previousCollege->sum->qualityPointsUsedToCalculateGpa = $rest[16];
                    }
                    if (isset($rest[17])) {
                        $previousCollege->sum->academicSummarySource = $rest[17];
                    }
                    break;
                case 63:
                    // PCL-SES
                    if (is_object($sessionHeader)) {
                        if (is_object($courseRecord)) {
                            $sessionHeader->crss[] = $courseRecord;
                            $courseRecord = null;
                        }

                        $previousCollege->sess[] = $sessionHeader;
                        $sessionHeader = null;
                    }

                    $sessionHeader = new \stdClass();
                    $sessionHeader->ses = new \stdClass();
                    $sessionHeader->ses->sessionStartDate = $rest[0];
                    if (isset($rest[1])) {
                        $sessionHeader->ses->count = $rest[1];
                    }
                    if (isset($rest[3])) {
                        $sessionHeader->ses->sessionCode = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $sessionHeader->ses->name = $rest[4];
                    }
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
                        $sessionHeader->ses->endDate= $rest[8];
                    }
                    if (isset($rest[9])) {
                        $sessionHeader->ses->courseCode = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $sessionHeader->ses->identificationCodeQualifier = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $sessionHeader->ses->identificationCode = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $sessionHeader->ses->name2 = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $sessionHeader->ses->reasonCode = $rest[13];
                    }
                    break;
                case 64:
                    // LX-MSG
                    $tmp = new \stdClass();
                    $tmp->messageText = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->controlCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->number = $rest[2];
                    }

                    $assignedNumber->msgs[] = $tmp;
                    $tmp = null;
                    break;
                case 65:
                    // LT-DTP
                    $letterOfRec->dtp = new \stdClass();
                    $letterOfRec->dtp->dateTimeQualifier = $rest[0];
                    $letterOfRec->dtp->dateTimePeriodFormatQualifier = $rest[1];
                    $letterOfRec->dtp->dateTimePeriod = $rest[2];
                    break;
                case 66:
                    // LT-QTY
                    $letterOfRec->qty = new \stdClass();
                    $letterOfRec->qty->quantityQualifier = $rest[0];
                    if (isset($rest[1])) {
                        $letterOfRec->qty->quantity = $rest[1];
                    }
                    if (isset($rest[3])) {
                        $letterOfRec->qty->message = $rest[3];
                    }
                    break;
                case 67:
                    // LT-COM
                    $tmp = new \stdClass();
                    $tmp->communicationNumberQualifier = $rest[0];
                    $tmp->communicationNumber = $rest[1];

                    $letterOfRec->coms[] = $tmp;
                    $tmp = null;
                    break;
                case 68:
                    // LT-N1
                    $letterOfRec->n1 = new \stdClass();
                    $letterOfRec->n1->entityIdentifierCode = $rest[0];
                    if (isset($rest[1])) {
                        $letterOfRec->n1->name = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $letterOfRec->n1->identificationCodeQualifier = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $letterOfRec->n1->identificationCode = $rest[3];
                    }
                    break;
                case 69:
                    // LT-N3
                    $letterOfRec->n3 = new \stdClass();
                    $letterOfRec->n3->address01 = $rest[0];
                    if (isset($rest[1])) {
                        $letterOfRec->n3->address02 = $rest[1];
                    }
                    break;
                case 70:
                    // LT-N4
                    $letterOfRec->n4 = new \stdClass();
                    if (isset($rest[0])) {
                        $letterOfRec->n4->city = $rest[0];
                    }
                    if (isset($rest[1])) {
                        $letterOfRec->n4->state = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $letterOfRec->n4->postalCode = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $letterOfRec->n4->countryCode = $rest[3];
                    }
                    break;
                case 71:
                    // LT-LTE
                    $tmp = new \stdClass();
                    if (isset($rest[0])) {
                        $tmp->codeListQualifierCode = $rest[0];
                    }
                    if (isset($rest[1])) {
                        $tmp->industryCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->description = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $tmp->ratingSummaryValueCode = $rest[3];
                    }

                    $letterOfRec->ltes[] = $tmp;
                    $tmp = null;
                    break;
                case 72:
                    // LT-MSG
                    $tmp = new \stdClass();
                    $tmp->messageText = $rest[0];
                    if (isset($rest[1])) {
                        $tmp->controlCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $tmp->number = $rest[2];
                    }

                    $letterOfRec->msgs[] = $tmp;
                    $tmp = null;
                    break;
                case 73:
                    // GE
                    // nop
                    break;
                case 74:
                    // IN1-N1-EMS-DTP
                    $employmentPosition->dtp = new \stdClass();
                    $employmentPosition->dtp->qualifer = $rest[0];
                    $employmentPosition->dtp->format = $rest[1];
                    $employmentPosition->dtp->date = $rest[2];
                    break;
                case 75:
                    // IN1-N1-EMS-QTY
                    $employmentPosition->qty = new \stdClass();
                    $employmentPosition->qty->qualifier = $rest[0];
                    $employmentPosition->qty->quantity = $rest[1];
                    $employmentPosition->qty->unitOfMeasure = $rest[2];
                    $employmentPosition->qty->basisForMeasurementCode = $rest[3];
                    $employmentPosition->qty->exponent = $rest[4];
                    $employmentPosition->qty->multiplier = $rest[5];
                    break;
                case 76:
                    // SST-SES-CRS
                    if (is_object($courseRecord)) {
                        $sessionHeader->crss[] = $courseRecord;
                        $courseRecord = null;
                    }

                    $courseRecord = new \stdClass();
                    $courseRecord->crs = new \stdClass();
                    $courseRecord->crs->creditCode = $rest[0];
                    if (isset($rest[1])) {
                        $courseRecord->crs->creditTypeCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $courseRecord->crs->creditsWorth = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $courseRecord->crs->creditsEarned = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $courseRecord->crs->academicGradeQualifier = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $courseRecord->crs->academicGrade = $rest[5];
                    }
                    if (isset($rest[7])) {
                        $courseRecord->crs->courseLevelCode = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $courseRecord->crs->courseRepeat = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $courseRecord->crs->identificationCodeQualifier = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $courseRecord->crs->identificationCode = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $courseRecord->crs->quantity = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $courseRecord->crs->level = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $courseRecord->crs->name = $rest[13];
                    }
                    if (isset($rest[15])) {
                        $courseRecord->crs->courseTitle = $rest[15];
                    }
                    if (isset($rest[18])) {
                        $courseRecord->crs->date = $rest[18];
                    }
                    break;
                case 77:
                    // TST-SBT-SRE
                    $tmp = new \stdClass();
                    $tmp->qualifierCode = $rest[0];
                    $tmp->description = $rest[1];

                    $subTest->sres[] = $tmp;
                    $tmp = null;
                    break;
                case 78:
                    // TST-SBT-NTE
                    $tmp = new \stdClass();
                    if (isset($rest[0])) {
                        $tmp->referenceCode = $rest[0];
                    }
                    $tmp->description = $rest[1];
                    break;
                case 79:
                    // PCL-SES-CRS
                    if (is_object($courseRecord)) {
                        $sessionHeader->crss[] = $courseRecord;
                        $courseRecord = null;
                    }

                    $courseRecord = new \stdClass();
                    $courseRecord->crs = new \stdClass();
                    $courseRecord->crs->creditCode = $rest[0];
                    if (isset($rest[1])) {
                        $courseRecord->crs->creditTypeCode = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $courseRecord->crs->creditsWorth = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $courseRecord->crs->creditsEarned = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $courseRecord->crs->academicGradeQualifier = $rest[4];
                    }
                    if (isset($rest[5])) {
                        $courseRecord->crs->academicGrade = $rest[5];
                    }
                    if (isset($rest[7])) {
                        $courseRecord->crs->courseLevelCode = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $courseRecord->crs->courseRepeat = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $courseRecord->crs->identificationCodeQualifier = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $courseRecord->crs->identificationCode = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $courseRecord->crs->quantity = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $courseRecord->crs->level = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $courseRecord->crs->name = $rest[13];
                    }
                    if (isset($rest[15])) {
                        $courseRecord->crs->courseTitle = $rest[15];
                    }
                    if (isset($rest[18])) {
                        $courseRecord->crs->date = $rest[18];
                    }
                    break;
                case 80:
                    // PCL-SES-DEG
                    if (is_object($courseRecord)) {
                        $sessionHeader->crss[] = $courseRecord;
                        $courseRecord = null;
                    }

                    if (is_object($degreeRecord)) {
                        $sessionHeader->degs[] = $degreeRecord;
                        $degreeRecord = null;
                    }

                    $degreeRecord = new \stdClass();
                    $degreeRecord->deg = new \stdClass();
                    $degreeRecord->deg->degreeCode = $rest[0];
                    if (isset($rest[1])) {
                        $degreeRecord->deg->dateDegreeAwardedFormatQualifier = $rest[1];
                    }
                    if (isset($rest[2])) {
                        $degreeRecord->deg->dateDegreeAwarded = $rest[2];
                    }
                    if (isset($rest[3])) {
                        $degreeRecord->deg->description = $rest[3];
                    }
                    if (isset($rest[4])) {
                        $degreeRecord->deg->reasonCode = $rest[4];
                    }
                    break;
                case 81:
                    // SST-SES-CRS-NTE
                    $courseRecord->nte = new \stdClass();
                    if (isset($rest[0])) {
                        $courseRecord->nte->referenceCode = $rest[0];
                    }
                    $courseRecord->nte->description = $rest[1];
                    break;
                case 82:
                    // TST-SBT-SRE-NTE
                    error_log("Unreachable State");
                    break;
                case 83:
                    // PCL-SES-CRS-NTE
                    $courseRecord->nte = new \stdClass();
                    if (isset($rest[0])) {
                        $courseRecord->nte->referenceCode = $rest[0];
                    }
                    $courseRecord->nte->description = $rest[1];
                    break;
                case 84:
                    // PCL-SES-DEG-SUM
                    $degreeRecord->sum = new \stdClass();
                    $degreeRecord->sum->creditTypeCode = $rest[0];
                    $degreeRecord->sum->gradeOrCourseLevelCode = $rest[1];
                    $degreeRecord->sum->cumulativeSummaryIndicator = $rest[2];
                    $degreeRecord->sum->creditHoursIncluded = $rest[3];
                    $degreeRecord->sum->creditHoursAttempted = $rest[4];
                    $degreeRecord->sum->creditHoursEarned = $rest[5];
                    if (isset($rest[6])) {
                        $degreeRecord->sum->lowestPossibleGradePointAverage = $rest[6];
                    }
                    if (isset($rest[7])) {
                        $degreeRecord->sum->highestPossibleGradePointAverage = $rest[7];
                    }
                    if (isset($rest[8])) {
                        $degreeRecord->sum->gradePointAverage = $rest[8];
                    }
                    if (isset($rest[9])) {
                        $degreeRecord->sum->excessiveGpaIndicator = $rest[9];
                    }
                    if (isset($rest[10])) {
                        $degreeRecord->sum->classRank = $rest[10];
                    }
                    if (isset($rest[11])) {
                        $degreeRecord->sum->classSize = $rest[11];
                    }
                    if (isset($rest[12])) {
                        $degreeRecord->sum->classRankDateFormatQualifier = $rest[12];
                    }
                    if (isset($rest[13])) {
                        $degreeRecord->sum->classRankDate = $rest[13];
                    }
                    if (isset($rest[16])) {
                        $degreeRecord->sum->qualityPointsUsedToCalculateGpa = $rest[16];
                    }
                    if (isset($rest[17])) {
                        $degreeRecord->sum->academicSummarySource = $rest[17];
                    }
                    break;
                case 85:
                    // PCL-SES-DEG-SUM-FOS
                    $tmp = new \stdClass();
                    $tmp->typeCode = $rest[0];
                    $tmp->identificationCodeQualifier = $rest[1];
                    $tmp->identificationCode = $rest[2];
                    $tmp->fieldOfStudyLiteral = $rest[3];
                    $tmp->fieldOfStudyHonorsLiteral = $rest[4];
                    $tmp->quantity = $rest[6];

                    $degreeRecord->foss[] = $tmp;
                    $tmp = null;
                    break;
                case 86:
                    // PCL-SES-DEG-SUM-NTE
                    $tmp = new \stdClass();
                    if (isset($rest[0])) {
                        $tmp->referenceCode = $rest[0];
                    }
                    $tmp->description = $rest[1];

                    $degreeRecord->ntes[] = $tmp;
                    $tmp = null;
                    break;
                case 99:
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

        $fsa[99] = 'ERROR';

        $fsa[0]['ISA'] = 1;  $fsa[1]['ISA'] = 99; $fsa[2]['ISA'] = 99; $fsa[3]['ISA'] = 99; $fsa[4]['ISA'] = 99;
        $fsa[0]['GS']  = 99; $fsa[1]['GS']  = 2;  $fsa[2]['GS']  = 99; $fsa[3]['GS']  = 99; $fsa[4]['GS']  = 99;
        $fsa[0]['ST']  = 99; $fsa[1]['ST']  = 99; $fsa[2]['ST']  = 3;  $fsa[3]['ST']  = 99; $fsa[4]['ST']  = 99;
        $fsa[0]['BGN'] = 99; $fsa[1]['BGN'] = 99; $fsa[2]['BGN'] = 99; $fsa[3]['BGN'] = 4;  $fsa[4]['BGN'] = 99;
        $fsa[0]['N1']  = 99; $fsa[1]['N1']  = 99; $fsa[2]['N1']  = 99; $fsa[3]['N1']  = 99; $fsa[4]['N1']  = 5;
        $fsa[0]['N2']  = 99; $fsa[1]['N2']  = 99; $fsa[2]['N2']  = 99; $fsa[3]['N2']  = 99; $fsa[4]['N2']  = 99;
        $fsa[0]['N3']  = 99; $fsa[1]['N3']  = 99; $fsa[2]['N3']  = 99; $fsa[3]['N3']  = 99; $fsa[4]['N3']  = 99;
        $fsa[0]['N4']  = 99; $fsa[1]['N4']  = 99; $fsa[2]['N4']  = 99; $fsa[3]['N4']  = 99; $fsa[4]['N4']  = 99;
        $fsa[0]['PER'] = 99; $fsa[1]['PER'] = 99; $fsa[2]['PER'] = 99; $fsa[3]['PER'] = 99; $fsa[4]['PER'] = 99;
        $fsa[0]['REF'] = 99; $fsa[1]['REF'] = 99; $fsa[2]['REF'] = 99; $fsa[3]['REF'] = 99; $fsa[4]['REF'] = 99;
        $fsa[0]['DTP'] = 99; $fsa[1]['DTP'] = 99; $fsa[2]['DTP'] = 99; $fsa[3]['DTP'] = 99; $fsa[4]['DTP'] = 99;
        $fsa[0]['IN1'] = 99; $fsa[1]['IN1'] = 99; $fsa[2]['IN1'] = 99; $fsa[3]['IN1'] = 99; $fsa[4]['IN1'] = 99;
        $fsa[0]['IN2'] = 99; $fsa[1]['IN2'] = 99; $fsa[2]['IN2'] = 99; $fsa[3]['IN2'] = 99; $fsa[4]['IN2'] = 99;
        $fsa[0]['DMG'] = 99; $fsa[1]['DMG'] = 99; $fsa[2]['DMG'] = 99; $fsa[3]['DMG'] = 99; $fsa[4]['DMG'] = 99;
        $fsa[0]['IND'] = 99; $fsa[1]['IND'] = 99; $fsa[2]['IND'] = 99; $fsa[3]['IND'] = 99; $fsa[4]['IND'] = 99;
        $fsa[0]['IMM'] = 99; $fsa[1]['IMM'] = 99; $fsa[2]['IMM'] = 99; $fsa[3]['IMM'] = 99; $fsa[4]['IMM'] = 99;
        $fsa[0]['LUI'] = 99; $fsa[1]['LUI'] = 99; $fsa[2]['LUI'] = 99; $fsa[3]['LUI'] = 99; $fsa[4]['LUI'] = 99;
        $fsa[0]['III'] = 99; $fsa[1]['III'] = 99; $fsa[2]['III'] = 99; $fsa[3]['III'] = 99; $fsa[4]['III'] = 99;
        $fsa[0]['NTE'] = 99; $fsa[1]['NTE'] = 99; $fsa[2]['NTE'] = 99; $fsa[3]['NTE'] = 99; $fsa[4]['NTE'] = 99;
        $fsa[0]['COM'] = 99; $fsa[1]['COM'] = 99; $fsa[2]['COM'] = 99; $fsa[3]['COM'] = 99; $fsa[4]['COM'] = 99;
        $fsa[0]['EMS'] = 99; $fsa[1]['EMS'] = 99; $fsa[2]['EMS'] = 99; $fsa[3]['EMS'] = 99; $fsa[4]['EMS'] = 99;
        $fsa[0]['QTY'] = 99; $fsa[1]['QTY'] = 99; $fsa[2]['QTY'] = 99; $fsa[3]['QTY'] = 99; $fsa[4]['QTY'] = 99;
        $fsa[0]['ATV'] = 99; $fsa[1]['ATV'] = 99; $fsa[2]['ATV'] = 99; $fsa[3]['ATV'] = 99; $fsa[4]['ATV'] = 99;
        $fsa[0]['AMT'] = 99; $fsa[1]['AMT'] = 99; $fsa[2]['AMT'] = 99; $fsa[3]['AMT'] = 99; $fsa[4]['AMT'] = 99;
        $fsa[0]['MSG'] = 99; $fsa[1]['MSG'] = 99; $fsa[2]['MSG'] = 99; $fsa[3]['MSG'] = 99; $fsa[4]['MSG'] = 99;
        $fsa[0]['SSE'] = 99; $fsa[1]['SSE'] = 99; $fsa[2]['SSE'] = 99; $fsa[3]['SSE'] = 99; $fsa[4]['SSE'] = 99;
        $fsa[0]['DEG'] = 99; $fsa[1]['DEG'] = 99; $fsa[2]['DEG'] = 99; $fsa[3]['DEG'] = 99; $fsa[4]['DEG'] = 99;
        $fsa[0]['FOS'] = 99; $fsa[1]['FOS'] = 99; $fsa[2]['FOS'] = 99; $fsa[3]['FOS'] = 99; $fsa[4]['FOS'] = 99;
        $fsa[0]['RSD'] = 99; $fsa[1]['RSD'] = 99; $fsa[2]['RSD'] = 99; $fsa[3]['RSD'] = 99; $fsa[4]['RSD'] = 99;
        $fsa[0]['RQS'] = 99; $fsa[1]['RQS'] = 99; $fsa[2]['RQS'] = 99; $fsa[3]['RQS'] = 99; $fsa[4]['RQS'] = 99;
        $fsa[0]['SST'] = 99; $fsa[1]['SST'] = 99; $fsa[2]['SST'] = 99; $fsa[3]['SST'] = 99; $fsa[4]['SST'] = 99;
        $fsa[0]['SUM'] = 99; $fsa[1]['SUM'] = 99; $fsa[2]['SUM'] = 99; $fsa[3]['SUM'] = 99; $fsa[4]['SUM'] = 99;
        $fsa[0]['SES'] = 99; $fsa[1]['SES'] = 99; $fsa[2]['SES'] = 99; $fsa[3]['SES'] = 99; $fsa[4]['SES'] = 99;
        $fsa[0]['CRS'] = 99; $fsa[1]['CRS'] = 99; $fsa[2]['CRS'] = 99; $fsa[3]['CRS'] = 99; $fsa[4]['CRS'] = 99;
        $fsa[0]['TST'] = 99; $fsa[1]['TST'] = 99; $fsa[2]['TST'] = 99; $fsa[3]['TST'] = 99; $fsa[4]['TST'] = 99;
        $fsa[0]['SBT'] = 99; $fsa[1]['SBT'] = 99; $fsa[2]['SBT'] = 99; $fsa[3]['SBT'] = 99; $fsa[4]['SBT'] = 99;
        $fsa[0]['SRE'] = 99; $fsa[1]['SRE'] = 99; $fsa[2]['SRE'] = 99; $fsa[3]['SRE'] = 99; $fsa[4]['SRE'] = 99;
        $fsa[0]['PCL'] = 99; $fsa[1]['PCL'] = 99; $fsa[2]['PCL'] = 99; $fsa[3]['PCL'] = 99; $fsa[4]['PCL'] = 99;
        $fsa[0]['LX']  = 99; $fsa[1]['LX']  = 99; $fsa[2]['LX']  = 99; $fsa[3]['LX']  = 99; $fsa[4]['LX']  = 99;
        $fsa[0]['LT']  = 99; $fsa[1]['LT']  = 99; $fsa[2]['LT']  = 99; $fsa[3]['LT']  = 99; $fsa[4]['LT']  = 99;
        $fsa[0]['LTE'] = 99; $fsa[1]['LTE'] = 99; $fsa[2]['LTE'] = 99; $fsa[3]['LTE'] = 99; $fsa[4]['LTE'] = 99;
        $fsa[0]['SE']  = 99; $fsa[1]['SE']  = 99; $fsa[2]['SE']  = 99; $fsa[3]['SE']  = 99; $fsa[4]['SE']  = 99;
        $fsa[0]['GE']  = 99; $fsa[1]['GE']  = 99; $fsa[2]['GE']  = 99; $fsa[3]['GE']  = 99; $fsa[4]['GE']  = 99;
        $fsa[0]['IEA'] = 99; $fsa[1]['IEA'] = 99; $fsa[2]['IEA'] = 99; $fsa[3]['IEA'] = 99; $fsa[4]['IEA'] = 99;

        $fsa[5]['ISA'] = 99; $fsa[6]['ISA'] = 99; $fsa[7]['ISA'] = 99; $fsa[8]['ISA'] = 99; $fsa[9]['ISA'] = 99;
        $fsa[5]['GS']  = 99; $fsa[6]['GS']  = 99; $fsa[7]['GS']  = 99; $fsa[8]['GS']  = 99; $fsa[9]['GS']  = 99;
        $fsa[5]['ST']  = 99; $fsa[6]['ST']  = 99; $fsa[7]['ST']  = 99; $fsa[8]['ST']  = 99; $fsa[9]['ST']  = 99;
        $fsa[5]['BGN'] = 99; $fsa[6]['BGN'] = 99; $fsa[7]['BGN'] = 99; $fsa[8]['BGN'] = 99; $fsa[9]['BGN'] = 99;
        $fsa[5]['N1']  = 5;  $fsa[6]['N1']  = 5;  $fsa[7]['N1']  = 5;  $fsa[8]['N1']  = 5;  $fsa[9]['N1']  = 5;
        $fsa[5]['N2']  = 6;  $fsa[6]['N2']  = 99; $fsa[7]['N2']  = 99; $fsa[8]['N2']  = 99; $fsa[9]['N2']  = 99;
        $fsa[5]['N3']  = 7;  $fsa[6]['N3']  = 7;  $fsa[7]['N3']  = 99; $fsa[8]['N3']  = 99; $fsa[9]['N3']  = 99;
        $fsa[5]['N4']  = 8;  $fsa[6]['N4']  = 8;  $fsa[7]['N4']  = 8;  $fsa[8]['N4']  = 99; $fsa[9]['N4']  = 99;
        $fsa[5]['PER'] = 9;  $fsa[6]['PER'] = 9;  $fsa[7]['PER'] = 9;  $fsa[8]['PER'] = 9;  $fsa[9]['PER'] = 99;
        $fsa[5]['REF'] = 10; $fsa[6]['REF'] = 10; $fsa[7]['REF'] = 10; $fsa[8]['REF'] = 10; $fsa[9]['REF'] = 10;
        $fsa[5]['DTP'] = 99; $fsa[6]['DTP'] = 99; $fsa[7]['DTP'] = 99; $fsa[8]['DTP'] = 99; $fsa[9]['DTP'] = 99;
        $fsa[5]['IN1'] = 99; $fsa[6]['IN1'] = 99; $fsa[7]['IN1'] = 99; $fsa[8]['IN1'] = 99; $fsa[9]['IN1'] = 99;
        $fsa[5]['IN2'] = 99; $fsa[6]['IN2'] = 99; $fsa[7]['IN2'] = 99; $fsa[8]['IN2'] = 99; $fsa[9]['IN2'] = 99;
        $fsa[5]['DMG'] = 99; $fsa[6]['DMG'] = 99; $fsa[7]['DMG'] = 99; $fsa[8]['DMG'] = 99; $fsa[9]['DMG'] = 99;
        $fsa[5]['IND'] = 99; $fsa[6]['IND'] = 99; $fsa[7]['IND'] = 99; $fsa[8]['IND'] = 99; $fsa[9]['IND'] = 99;
        $fsa[5]['IMM'] = 99; $fsa[6]['IMM'] = 99; $fsa[7]['IMM'] = 99; $fsa[8]['IMM'] = 99; $fsa[9]['IMM'] = 99;
        $fsa[5]['LUI'] = 99; $fsa[6]['LUI'] = 99; $fsa[7]['LUI'] = 99; $fsa[8]['LUI'] = 99; $fsa[9]['LUI'] = 99;
        $fsa[5]['III'] = 99; $fsa[6]['III'] = 99; $fsa[7]['III'] = 99; $fsa[8]['III'] = 99; $fsa[9]['III'] = 99;
        $fsa[5]['NTE'] = 99; $fsa[6]['NTE'] = 99; $fsa[7]['NTE'] = 99; $fsa[8]['NTE'] = 99; $fsa[9]['NTE'] = 99;
        $fsa[5]['COM'] = 99; $fsa[6]['COM'] = 99; $fsa[7]['COM'] = 99; $fsa[8]['COM'] = 99; $fsa[9]['COM'] = 99;
        $fsa[5]['EMS'] = 99; $fsa[6]['EMS'] = 99; $fsa[7]['EMS'] = 99; $fsa[8]['EMS'] = 99; $fsa[9]['EMS'] = 99;
        $fsa[5]['QTY'] = 99; $fsa[6]['QTY'] = 99; $fsa[7]['QTY'] = 99; $fsa[8]['QTY'] = 99; $fsa[9]['QTY'] = 99;
        $fsa[5]['ATV'] = 99; $fsa[6]['ATV'] = 99; $fsa[7]['ATV'] = 99; $fsa[8]['ATV'] = 99; $fsa[9]['ATV'] = 99;
        $fsa[5]['AMT'] = 99; $fsa[6]['AMT'] = 99; $fsa[7]['AMT'] = 99; $fsa[8]['AMT'] = 99; $fsa[9]['AMT'] = 99;
        $fsa[5]['MSG'] = 99; $fsa[6]['MSG'] = 99; $fsa[7]['MSG'] = 99; $fsa[8]['MSG'] = 99; $fsa[9]['MSG'] = 99;
        $fsa[5]['SSE'] = 99; $fsa[6]['SSE'] = 99; $fsa[7]['SSE'] = 99; $fsa[8]['SSE'] = 99; $fsa[9]['SSE'] = 99;
        $fsa[5]['DEG'] = 99; $fsa[6]['DEG'] = 99; $fsa[7]['DEG'] = 99; $fsa[8]['DEG'] = 99; $fsa[9]['DEG'] = 99;
        $fsa[5]['FOS'] = 99; $fsa[6]['FOS'] = 99; $fsa[7]['FOS'] = 99; $fsa[8]['FOS'] = 99; $fsa[9]['FOS'] = 99;
        $fsa[5]['RSD'] = 99; $fsa[6]['RSD'] = 99; $fsa[7]['RSD'] = 99; $fsa[8]['RSD'] = 99; $fsa[9]['RSD'] = 99;
        $fsa[5]['RQS'] = 99; $fsa[6]['RQS'] = 99; $fsa[7]['RQS'] = 99; $fsa[8]['RQS'] = 99; $fsa[9]['RQS'] = 99;
        $fsa[5]['SST'] = 99; $fsa[6]['SST'] = 99; $fsa[7]['SST'] = 99; $fsa[8]['SST'] = 99; $fsa[9]['SST'] = 99;
        $fsa[5]['SUM'] = 99; $fsa[6]['SUM'] = 99; $fsa[7]['SUM'] = 99; $fsa[8]['SUM'] = 99; $fsa[9]['SUM'] = 99;
        $fsa[5]['SES'] = 99; $fsa[6]['SES'] = 99; $fsa[7]['SES'] = 99; $fsa[8]['SES'] = 99; $fsa[9]['SES'] = 99;
        $fsa[5]['CRS'] = 99; $fsa[6]['CRS'] = 99; $fsa[7]['CRS'] = 99; $fsa[8]['CRS'] = 99; $fsa[9]['CRS'] = 99;
        $fsa[5]['TST'] = 99; $fsa[6]['TST'] = 99; $fsa[7]['TST'] = 99; $fsa[8]['TST'] = 99; $fsa[9]['TST'] = 99;
        $fsa[5]['SBT'] = 99; $fsa[6]['SBT'] = 99; $fsa[7]['SBT'] = 99; $fsa[8]['SBT'] = 99; $fsa[9]['SBT'] = 99;
        $fsa[5]['SRE'] = 99; $fsa[6]['SRE'] = 99; $fsa[7]['SRE'] = 99; $fsa[8]['SRE'] = 99; $fsa[9]['SRE'] = 99;
        $fsa[5]['PCL'] = 99; $fsa[6]['PCL'] = 99; $fsa[7]['PCL'] = 99; $fsa[8]['PCL'] = 99; $fsa[9]['PCL'] = 99;
        $fsa[5]['LX']  = 99; $fsa[6]['LX']  = 99; $fsa[7]['LX']  = 99; $fsa[8]['LX']  = 99; $fsa[9]['LX']  = 99;
        $fsa[5]['LT']  = 99; $fsa[6]['LT']  = 99; $fsa[7]['LT']  = 99; $fsa[8]['LT']  = 99; $fsa[9]['LT']  = 99;
        $fsa[5]['LTE'] = 99; $fsa[6]['LTE'] = 99; $fsa[7]['LTE'] = 99; $fsa[8]['LTE'] = 99; $fsa[9]['LTE'] = 99;
        $fsa[5]['SE']  = 99; $fsa[6]['SE']  = 99; $fsa[7]['SE']  = 99; $fsa[8]['SE']  = 99; $fsa[9]['SE']  = 99;
        $fsa[5]['GE']  = 99; $fsa[6]['GE']  = 99; $fsa[7]['GE']  = 99; $fsa[8]['GE']  = 99; $fsa[9]['GE']  = 99;
        $fsa[5]['IEA'] = 99; $fsa[6]['IEA'] = 99; $fsa[7]['IEA'] = 99; $fsa[8]['IEA'] = 99; $fsa[9]['IEA'] = 99;

        $fsa[10]['ISA'] = 99; $fsa[11]['ISA'] = 99; $fsa[12]['ISA'] = 99; $fsa[13]['ISA'] = 99; $fsa[14]['ISA'] = 99;
        $fsa[10]['GS']  = 99; $fsa[11]['GS']  = 99; $fsa[12]['GS']  = 99; $fsa[13]['GS']  = 99; $fsa[14]['GS']  = 99;
        $fsa[10]['ST']  = 99; $fsa[11]['ST']  = 99; $fsa[12]['ST']  = 99; $fsa[13]['ST']  = 99; $fsa[14]['ST']  = 99;
        $fsa[10]['BGN'] = 99; $fsa[11]['BGN'] = 99; $fsa[12]['BGN'] = 99; $fsa[13]['BGN'] = 99; $fsa[14]['BGN'] = 99;
        $fsa[10]['N1']  = 13; $fsa[11]['N1']  = 13; $fsa[12]['N1']  = 13; $fsa[13]['N1']  = 99; $fsa[14]['N1']  = 99;
        $fsa[10]['N2']  = 99; $fsa[11]['N2']  = 99; $fsa[12]['N2']  = 99; $fsa[13]['N2']  = 99; $fsa[14]['N2']  = 99;
        $fsa[10]['N3']  = 99; $fsa[11]['N3']  = 99; $fsa[12]['N3']  = 99; $fsa[13]['N3']  = 99; $fsa[14]['N3']  = 99;
        $fsa[10]['N4']  = 12; $fsa[11]['N4']  = 12; $fsa[12]['N4']  = 99; $fsa[13]['N4']  = 99; $fsa[14]['N4']  = 99;
        $fsa[10]['PER'] = 99; $fsa[11]['PER'] = 99; $fsa[12]['PER'] = 99; $fsa[13]['PER'] = 99; $fsa[14]['PER'] = 99;
        $fsa[10]['REF'] = 10; $fsa[11]['REF'] = 10; $fsa[12]['REF'] = 10; $fsa[13]['REF'] = 10; $fsa[14]['REF'] = 99;
        $fsa[10]['DTP'] = 11; $fsa[11]['DTP'] = 11; $fsa[12]['DTP'] = 99; $fsa[13]['DTP'] = 99; $fsa[14]['DTP'] = 99;
        $fsa[10]['IN1'] = 14; $fsa[11]['IN1'] = 14; $fsa[12]['IN1'] = 14; $fsa[13]['IN1'] = 14; $fsa[14]['IN1'] = 99;
        $fsa[10]['IN2'] = 99; $fsa[11]['IN2'] = 99; $fsa[12]['IN2'] = 99; $fsa[13]['IN2'] = 99; $fsa[14]['IN2'] = 15;
        $fsa[10]['DMG'] = 99; $fsa[11]['DMG'] = 99; $fsa[12]['DMG'] = 99; $fsa[13]['DMG'] = 99; $fsa[14]['DMG'] = 99;
        $fsa[10]['IND'] = 99; $fsa[11]['IND'] = 99; $fsa[12]['IND'] = 99; $fsa[13]['IND'] = 99; $fsa[14]['IND'] = 99;
        $fsa[10]['IMM'] = 99; $fsa[11]['IMM'] = 99; $fsa[12]['IMM'] = 99; $fsa[13]['IMM'] = 99; $fsa[14]['IMM'] = 99;
        $fsa[10]['LUI'] = 99; $fsa[11]['LUI'] = 99; $fsa[12]['LUI'] = 99; $fsa[13]['LUI'] = 99; $fsa[14]['LUI'] = 99;
        $fsa[10]['III'] = 99; $fsa[11]['III'] = 99; $fsa[12]['III'] = 99; $fsa[13]['III'] = 99; $fsa[14]['III'] = 99;
        $fsa[10]['NTE'] = 99; $fsa[11]['NTE'] = 99; $fsa[12]['NTE'] = 99; $fsa[13]['NTE'] = 99; $fsa[14]['NTE'] = 99;
        $fsa[10]['COM'] = 99; $fsa[11]['COM'] = 99; $fsa[12]['COM'] = 99; $fsa[13]['COM'] = 99; $fsa[14]['COM'] = 99;
        $fsa[10]['EMS'] = 99; $fsa[11]['EMS'] = 99; $fsa[12]['EMS'] = 99; $fsa[13]['EMS'] = 99; $fsa[14]['EMS'] = 99;
        $fsa[10]['QTY'] = 99; $fsa[11]['QTY'] = 99; $fsa[12]['QTY'] = 99; $fsa[13]['QTY'] = 99; $fsa[14]['QTY'] = 99;
        $fsa[10]['ATV'] = 99; $fsa[11]['ATV'] = 99; $fsa[12]['ATV'] = 99; $fsa[13]['ATV'] = 99; $fsa[14]['ATV'] = 99;
        $fsa[10]['AMT'] = 99; $fsa[11]['AMT'] = 99; $fsa[12]['AMT'] = 99; $fsa[13]['AMT'] = 99; $fsa[14]['AMT'] = 99;
        $fsa[10]['MSG'] = 99; $fsa[11]['MSG'] = 99; $fsa[12]['MSG'] = 99; $fsa[13]['MSG'] = 99; $fsa[14]['MSG'] = 99;
        $fsa[10]['SSE'] = 99; $fsa[11]['SSE'] = 99; $fsa[12]['SSE'] = 99; $fsa[13]['SSE'] = 99; $fsa[14]['SSE'] = 99;
        $fsa[10]['DEG'] = 99; $fsa[11]['DEG'] = 99; $fsa[12]['DEG'] = 99; $fsa[13]['DEG'] = 99; $fsa[14]['DEG'] = 99;
        $fsa[10]['FOS'] = 99; $fsa[11]['FOS'] = 99; $fsa[12]['FOS'] = 99; $fsa[13]['FOS'] = 99; $fsa[14]['FOS'] = 99;
        $fsa[10]['RSD'] = 99; $fsa[11]['RSD'] = 99; $fsa[12]['RSD'] = 99; $fsa[13]['RSD'] = 99; $fsa[14]['RSD'] = 99;
        $fsa[10]['RQS'] = 99; $fsa[11]['RQS'] = 99; $fsa[12]['RQS'] = 99; $fsa[13]['RQS'] = 99; $fsa[14]['RQS'] = 99;
        $fsa[10]['SST'] = 99; $fsa[11]['SST'] = 99; $fsa[12]['SST'] = 99; $fsa[13]['SST'] = 99; $fsa[14]['SST'] = 99;
        $fsa[10]['SUM'] = 99; $fsa[11]['SUM'] = 99; $fsa[12]['SUM'] = 99; $fsa[13]['SUM'] = 99; $fsa[14]['SUM'] = 99;
        $fsa[10]['SES'] = 99; $fsa[11]['SES'] = 99; $fsa[12]['SES'] = 99; $fsa[13]['SES'] = 99; $fsa[14]['SES'] = 99;
        $fsa[10]['CRS'] = 99; $fsa[11]['CRS'] = 99; $fsa[12]['CRS'] = 99; $fsa[13]['CRS'] = 99; $fsa[14]['CRS'] = 99;
        $fsa[10]['TST'] = 99; $fsa[11]['TST'] = 99; $fsa[12]['TST'] = 99; $fsa[13]['TST'] = 99; $fsa[14]['TST'] = 99;
        $fsa[10]['SBT'] = 99; $fsa[11]['SBT'] = 99; $fsa[12]['SBT'] = 99; $fsa[13]['SBT'] = 99; $fsa[14]['SBT'] = 99;
        $fsa[10]['SRE'] = 99; $fsa[11]['SRE'] = 99; $fsa[12]['SRE'] = 99; $fsa[13]['SRE'] = 99; $fsa[14]['SRE'] = 99;
        $fsa[10]['PCL'] = 99; $fsa[11]['PCL'] = 99; $fsa[12]['PCL'] = 99; $fsa[13]['PCL'] = 99; $fsa[14]['PCL'] = 99;
        $fsa[10]['LX']  = 99; $fsa[11]['LX']  = 99; $fsa[12]['LX']  = 99; $fsa[13]['LX']  = 99; $fsa[14]['LX']  = 99;
        $fsa[10]['LT']  = 99; $fsa[11]['LT']  = 99; $fsa[12]['LT']  = 99; $fsa[13]['LT']  = 99; $fsa[14]['LT']  = 99;
        $fsa[10]['LTE'] = 99; $fsa[11]['LTE'] = 99; $fsa[12]['LTE'] = 99; $fsa[13]['LTE'] = 99; $fsa[14]['LTE'] = 99;
        $fsa[10]['SE']  = 99; $fsa[11]['SE']  = 99; $fsa[12]['SE']  = 99; $fsa[13]['SE']  = 99; $fsa[14]['SE']  = 99;
        $fsa[10]['GE']  = 99; $fsa[11]['GE']  = 99; $fsa[12]['GE']  = 99; $fsa[13]['GE']  = 99; $fsa[14]['GE']  = 99;
        $fsa[10]['IEA'] = 99; $fsa[11]['IEA'] = 99; $fsa[12]['IEA'] = 99; $fsa[13]['IEA'] = 99; $fsa[14]['IEA'] = 99;

        $fsa[15]['ISA'] = 99; $fsa[16]['ISA'] = 99; $fsa[17]['ISA'] = 99; $fsa[18]['ISA'] = 99; $fsa[19]['ISA'] = 99;
        $fsa[15]['GS']  = 99; $fsa[16]['GS']  = 99; $fsa[17]['GS']  = 99; $fsa[18]['GS']  = 99; $fsa[19]['GS']  = 99;
        $fsa[15]['ST']  = 99; $fsa[16]['ST']  = 99; $fsa[17]['ST']  = 99; $fsa[18]['ST']  = 99; $fsa[19]['ST']  = 99;
        $fsa[15]['BGN'] = 99; $fsa[16]['BGN'] = 99; $fsa[17]['BGN'] = 99; $fsa[18]['BGN'] = 99; $fsa[19]['BGN'] = 99;
        $fsa[15]['N1']  = 25; $fsa[16]['N1']  = 25; $fsa[17]['N1']  = 25; $fsa[18]['N1']  = 25; $fsa[19]['N1']  = 25;
        $fsa[15]['N2']  = 99; $fsa[16]['N2']  = 99; $fsa[17]['N2']  = 99; $fsa[18]['N2']  = 99; $fsa[19]['N2']  = 99;
        $fsa[15]['N3']  = 23; $fsa[16]['N3']  = 23; $fsa[17]['N3']  = 23; $fsa[18]['N3']  = 23; $fsa[19]['N3']  = 23;
        $fsa[15]['N4']  = 99; $fsa[16]['N4']  = 99; $fsa[17]['N4']  = 99; $fsa[18]['N4']  = 99; $fsa[19]['N4']  = 99;
        $fsa[15]['PER'] = 99; $fsa[16]['PER'] = 99; $fsa[17]['PER'] = 99; $fsa[18]['PER'] = 99; $fsa[19]['PER'] = 99;
        $fsa[15]['REF'] = 16; $fsa[16]['REF'] = 16; $fsa[17]['REF'] = 16; $fsa[18]['REF'] = 16; $fsa[19]['REF'] = 16;
        $fsa[15]['DTP'] = 99; $fsa[16]['DTP'] = 99; $fsa[17]['DTP'] = 99; $fsa[18]['DTP'] = 99; $fsa[19]['DTP'] = 99;
        $fsa[15]['IN1'] = 14; $fsa[16]['IN1'] = 14; $fsa[17]['IN1'] = 14; $fsa[18]['IN1'] = 14; $fsa[19]['IN1'] = 14;
        $fsa[15]['IN2'] = 15; $fsa[16]['IN2'] = 99; $fsa[17]['IN2'] = 99; $fsa[18]['IN2'] = 99; $fsa[19]['IN2'] = 99;
        $fsa[15]['DMG'] = 17; $fsa[16]['DMG'] = 17; $fsa[17]['DMG'] = 99; $fsa[18]['DMG'] = 99; $fsa[19]['DMG'] = 99;
        $fsa[15]['IND'] = 18; $fsa[16]['IND'] = 18; $fsa[17]['IND'] = 18; $fsa[18]['IND'] = 99; $fsa[19]['IND'] = 99;
        $fsa[15]['IMM'] = 19; $fsa[16]['IMM'] = 19; $fsa[17]['IMM'] = 19; $fsa[18]['IMM'] = 19; $fsa[19]['IMM'] = 19;
        $fsa[15]['LUI'] = 20; $fsa[16]['LUI'] = 20; $fsa[17]['LUI'] = 20; $fsa[18]['LUI'] = 20; $fsa[19]['LUI'] = 20;
        $fsa[15]['III'] = 21; $fsa[16]['III'] = 21; $fsa[17]['III'] = 21; $fsa[18]['III'] = 21; $fsa[19]['III'] = 21;
        $fsa[15]['NTE'] = 22; $fsa[16]['NTE'] = 22; $fsa[17]['NTE'] = 22; $fsa[18]['NTE'] = 22; $fsa[19]['NTE'] = 22;
        $fsa[15]['COM'] = 24; $fsa[16]['COM'] = 24; $fsa[17]['COM'] = 24; $fsa[18]['COM'] = 24; $fsa[19]['COM'] = 24;
        $fsa[15]['EMS'] = 99; $fsa[16]['EMS'] = 99; $fsa[17]['EMS'] = 99; $fsa[18]['EMS'] = 99; $fsa[19]['EMS'] = 99;
        $fsa[15]['QTY'] = 99; $fsa[16]['QTY'] = 99; $fsa[17]['QTY'] = 99; $fsa[18]['QTY'] = 99; $fsa[19]['QTY'] = 99;
        $fsa[15]['ATV'] = 26; $fsa[16]['ATV'] = 26; $fsa[17]['ATV'] = 26; $fsa[18]['ATV'] = 26; $fsa[19]['ATV'] = 26;
        $fsa[15]['AMT'] = 27; $fsa[16]['AMT'] = 27; $fsa[17]['AMT'] = 27; $fsa[18]['AMT'] = 27; $fsa[19]['AMT'] = 27;
        $fsa[15]['MSG'] = 99; $fsa[16]['MSG'] = 99; $fsa[17]['MSG'] = 99; $fsa[18]['MSG'] = 99; $fsa[19]['MSG'] = 99;
        $fsa[15]['SSE'] = 28; $fsa[16]['SSE'] = 28; $fsa[17]['SSE'] = 28; $fsa[18]['SSE'] = 28; $fsa[19]['SSE'] = 28;
        $fsa[15]['DEG'] = 99; $fsa[16]['DEG'] = 99; $fsa[17]['DEG'] = 99; $fsa[18]['DEG'] = 99; $fsa[19]['DEG'] = 99;
        $fsa[15]['FOS'] = 99; $fsa[16]['FOS'] = 99; $fsa[17]['FOS'] = 99; $fsa[18]['FOS'] = 99; $fsa[19]['FOS'] = 99;
        $fsa[15]['RSD'] = 29; $fsa[16]['RSD'] = 29; $fsa[17]['RSD'] = 29; $fsa[18]['RSD'] = 29; $fsa[19]['RSD'] = 29;
        $fsa[15]['RQS'] = 30; $fsa[16]['RQS'] = 30; $fsa[17]['RQS'] = 30; $fsa[18]['RQS'] = 30; $fsa[19]['RQS'] = 30;
        $fsa[15]['SST'] = 31; $fsa[16]['SST'] = 31; $fsa[17]['SST'] = 31; $fsa[18]['SST'] = 31; $fsa[19]['SST'] = 31;
        $fsa[15]['SUM'] = 99; $fsa[16]['SUM'] = 99; $fsa[17]['SUM'] = 99; $fsa[18]['SUM'] = 99; $fsa[19]['SUM'] = 99;
        $fsa[15]['SES'] = 99; $fsa[16]['SES'] = 99; $fsa[17]['SES'] = 99; $fsa[18]['SES'] = 99; $fsa[19]['SES'] = 99;
        $fsa[15]['CRS'] = 99; $fsa[16]['CRS'] = 99; $fsa[17]['CRS'] = 99; $fsa[18]['CRS'] = 99; $fsa[19]['CRS'] = 99;
        $fsa[15]['TST'] = 32; $fsa[16]['TST'] = 32; $fsa[17]['TST'] = 32; $fsa[18]['TST'] = 32; $fsa[19]['TST'] = 32;
        $fsa[15]['SBT'] = 99; $fsa[16]['SBT'] = 99; $fsa[17]['SBT'] = 99; $fsa[18]['SBT'] = 99; $fsa[19]['SBT'] = 99;
        $fsa[15]['SRE'] = 99; $fsa[16]['SRE'] = 99; $fsa[17]['SRE'] = 99; $fsa[18]['SRE'] = 99; $fsa[19]['SRE'] = 99;
        $fsa[15]['PCL'] = 33; $fsa[16]['PCL'] = 33; $fsa[17]['PCL'] = 33; $fsa[18]['PCL'] = 33; $fsa[19]['PCL'] = 33;
        $fsa[15]['LX']  = 34; $fsa[16]['LX']  = 34; $fsa[17]['LX']  = 34; $fsa[18]['LX']  = 34; $fsa[19]['LX']  = 34;
        $fsa[15]['LT']  = 35; $fsa[16]['LT']  = 35; $fsa[17]['LT']  = 35; $fsa[18]['LT']  = 35; $fsa[19]['LT']  = 35;
        $fsa[15]['LTE'] = 99; $fsa[16]['LTE'] = 99; $fsa[17]['LTE'] = 99; $fsa[18]['LTE'] = 99; $fsa[19]['LTE'] = 99;
        $fsa[15]['SE']  = 36; $fsa[16]['SE']  = 36; $fsa[17]['SE']  = 36; $fsa[18]['SE']  = 36; $fsa[19]['SE']  = 36;
        $fsa[15]['GE']  = 99; $fsa[16]['GE']  = 99; $fsa[17]['GE']  = 99; $fsa[18]['GE']  = 99; $fsa[19]['GE']  = 99;
        $fsa[15]['IEA'] = 99; $fsa[16]['IEA'] = 99; $fsa[17]['IEA'] = 99; $fsa[18]['IEA'] = 99; $fsa[19]['IEA'] = 99;

        $fsa[20]['ISA'] = 99; $fsa[21]['ISA'] = 99; $fsa[22]['ISA'] = 99; $fsa[23]['ISA'] = 99; $fsa[24]['ISA'] = 99;
        $fsa[20]['GS']  = 99; $fsa[21]['GS']  = 99; $fsa[22]['GS']  = 99; $fsa[23]['GS']  = 99; $fsa[24]['GS']  = 99;
        $fsa[20]['ST']  = 99; $fsa[21]['ST']  = 99; $fsa[22]['ST']  = 99; $fsa[23]['ST']  = 99; $fsa[24]['ST']  = 99;
        $fsa[20]['BGN'] = 99; $fsa[21]['BGN'] = 99; $fsa[22]['BGN'] = 99; $fsa[23]['BGN'] = 99; $fsa[24]['BGN'] = 99;
        $fsa[20]['N1']  = 25; $fsa[21]['N1']  = 25; $fsa[22]['N1']  = 25; $fsa[23]['N1']  = 25; $fsa[24]['N1']  = 25;
        $fsa[20]['N2']  = 99; $fsa[21]['N2']  = 99; $fsa[22]['N2']  = 99; $fsa[23]['N2']  = 99; $fsa[24]['N2']  = 99;
        $fsa[20]['N3']  = 23; $fsa[21]['N3']  = 23; $fsa[22]['N3']  = 23; $fsa[23]['N3']  = 23; $fsa[24]['N3']  = 99;
        $fsa[20]['N4']  = 99; $fsa[21]['N4']  = 99; $fsa[22]['N4']  = 99; $fsa[23]['N4']  = 37; $fsa[24]['N4']  = 99;
        $fsa[20]['PER'] = 99; $fsa[21]['PER'] = 99; $fsa[22]['PER'] = 99; $fsa[23]['PER'] = 99; $fsa[24]['PER'] = 99;
        $fsa[20]['REF'] = 99; $fsa[21]['REF'] = 99; $fsa[22]['REF'] = 99; $fsa[23]['REF'] = 99; $fsa[24]['REF'] = 99;
        $fsa[20]['DTP'] = 99; $fsa[21]['DTP'] = 99; $fsa[22]['DTP'] = 99; $fsa[23]['DTP'] = 38; $fsa[24]['DTP'] = 39;
        $fsa[20]['IN1'] = 14; $fsa[21]['IN1'] = 14; $fsa[22]['IN1'] = 14; $fsa[23]['IN1'] = 14; $fsa[24]['IN1'] = 14;
        $fsa[20]['IN2'] = 99; $fsa[21]['IN2'] = 99; $fsa[22]['IN2'] = 99; $fsa[23]['IN2'] = 99; $fsa[24]['IN2'] = 99;
        $fsa[20]['DMG'] = 99; $fsa[21]['DMG'] = 99; $fsa[22]['DMG'] = 99; $fsa[23]['DMG'] = 99; $fsa[24]['DMG'] = 99;
        $fsa[20]['IND'] = 99; $fsa[21]['IND'] = 99; $fsa[22]['IND'] = 99; $fsa[23]['IND'] = 99; $fsa[24]['IND'] = 99;
        $fsa[20]['IMM'] = 99; $fsa[21]['IMM'] = 99; $fsa[22]['IMM'] = 99; $fsa[23]['IMM'] = 99; $fsa[24]['IMM'] = 99;
        $fsa[20]['LUI'] = 20; $fsa[21]['LUI'] = 20; $fsa[22]['LUI'] = 20; $fsa[23]['LUI'] = 99; $fsa[24]['LUI'] = 99;
        $fsa[20]['III'] = 21; $fsa[21]['III'] = 21; $fsa[22]['III'] = 99; $fsa[23]['III'] = 99; $fsa[24]['III'] = 99;
        $fsa[20]['NTE'] = 22; $fsa[21]['NTE'] = 22; $fsa[22]['NTE'] = 99; $fsa[23]['NTE'] = 99; $fsa[24]['NTE'] = 99;
        $fsa[20]['COM'] = 24; $fsa[21]['COM'] = 24; $fsa[22]['COM'] = 24; $fsa[23]['COM'] = 24; $fsa[24]['COM'] = 24;
        $fsa[20]['EMS'] = 99; $fsa[21]['EMS'] = 99; $fsa[22]['EMS'] = 99; $fsa[23]['EMS'] = 99; $fsa[24]['EMS'] = 99;
        $fsa[20]['QTY'] = 99; $fsa[21]['QTY'] = 99; $fsa[22]['QTY'] = 99; $fsa[23]['QTY'] = 99; $fsa[24]['QTY'] = 99;
        $fsa[20]['ATV'] = 26; $fsa[21]['ATV'] = 26; $fsa[22]['ATV'] = 26; $fsa[23]['ATV'] = 26; $fsa[24]['ATV'] = 26;
        $fsa[20]['AMT'] = 27; $fsa[21]['AMT'] = 27; $fsa[22]['AMT'] = 27; $fsa[23]['AMT'] = 27; $fsa[24]['AMT'] = 27;
        $fsa[20]['MSG'] = 99; $fsa[21]['MSG'] = 99; $fsa[22]['MSG'] = 99; $fsa[23]['MSG'] = 99; $fsa[24]['MSG'] = 99;
        $fsa[20]['SSE'] = 28; $fsa[21]['SSE'] = 28; $fsa[22]['SSE'] = 28; $fsa[23]['SSE'] = 28; $fsa[24]['SSE'] = 28;
        $fsa[20]['DEG'] = 99; $fsa[21]['DEG'] = 99; $fsa[22]['DEG'] = 99; $fsa[23]['DEG'] = 99; $fsa[24]['DEG'] = 99;
        $fsa[20]['FOS'] = 99; $fsa[21]['FOS'] = 99; $fsa[22]['FOS'] = 99; $fsa[23]['FOS'] = 99; $fsa[24]['FOS'] = 99;
        $fsa[20]['RSD'] = 29; $fsa[21]['RSD'] = 29; $fsa[22]['RSD'] = 29; $fsa[23]['RSD'] = 29; $fsa[24]['RSD'] = 29;
        $fsa[20]['RQS'] = 30; $fsa[21]['RQS'] = 30; $fsa[22]['RQS'] = 30; $fsa[23]['RQS'] = 30; $fsa[24]['RQS'] = 30;
        $fsa[20]['SST'] = 31; $fsa[21]['SST'] = 31; $fsa[22]['SST'] = 31; $fsa[23]['SST'] = 31; $fsa[24]['SST'] = 31;
        $fsa[20]['SUM'] = 99; $fsa[21]['SUM'] = 99; $fsa[22]['SUM'] = 99; $fsa[23]['SUM'] = 99; $fsa[24]['SUM'] = 99;
        $fsa[20]['SES'] = 99; $fsa[21]['SES'] = 99; $fsa[22]['SES'] = 99; $fsa[23]['SES'] = 99; $fsa[24]['SES'] = 99;
        $fsa[20]['CRS'] = 99; $fsa[21]['CRS'] = 99; $fsa[22]['CRS'] = 99; $fsa[23]['CRS'] = 99; $fsa[24]['CRS'] = 99;
        $fsa[20]['TST'] = 32; $fsa[21]['TST'] = 32; $fsa[22]['TST'] = 32; $fsa[23]['TST'] = 32; $fsa[24]['TST'] = 32;
        $fsa[20]['SBT'] = 99; $fsa[21]['SBT'] = 99; $fsa[22]['SBT'] = 99; $fsa[23]['SBT'] = 99; $fsa[24]['SBT'] = 99;
        $fsa[20]['SRE'] = 99; $fsa[21]['SRE'] = 99; $fsa[22]['SRE'] = 99; $fsa[23]['SRE'] = 99; $fsa[24]['SRE'] = 99;
        $fsa[20]['PCL'] = 33; $fsa[21]['PCL'] = 33; $fsa[22]['PCL'] = 33; $fsa[23]['PCL'] = 33; $fsa[24]['PCL'] = 33;
        $fsa[20]['LX']  = 34; $fsa[21]['LX']  = 34; $fsa[22]['LX']  = 34; $fsa[23]['LX']  = 34; $fsa[24]['LX']  = 34;
        $fsa[20]['LT']  = 35; $fsa[21]['LT']  = 35; $fsa[22]['LT']  = 35; $fsa[23]['LT']  = 35; $fsa[24]['LT']  = 35;
        $fsa[20]['LTE'] = 99; $fsa[21]['LTE'] = 99; $fsa[22]['LTE'] = 99; $fsa[23]['LTE'] = 99; $fsa[24]['LTE'] = 99;
        $fsa[20]['SE']  = 36; $fsa[21]['SE']  = 36; $fsa[22]['SE']  = 36; $fsa[23]['SE']  = 36; $fsa[24]['SE']  = 36;
        $fsa[20]['GE']  = 99; $fsa[21]['GE']  = 99; $fsa[22]['GE']  = 99; $fsa[23]['GE']  = 99; $fsa[24]['GE']  = 99;
        $fsa[20]['IEA'] = 99; $fsa[21]['IEA'] = 99; $fsa[22]['IEA'] = 99; $fsa[23]['IEA'] = 99; $fsa[24]['IEA'] = 99;

        $fsa[25]['ISA'] = 99; $fsa[26]['ISA'] = 99; $fsa[27]['ISA'] = 99; $fsa[28]['ISA'] = 99; $fsa[29]['ISA'] = 99;
        $fsa[25]['GS']  = 99; $fsa[26]['GS']  = 99; $fsa[27]['GS']  = 99; $fsa[28]['GS']  = 99; $fsa[29]['GS']  = 99;
        $fsa[25]['ST']  = 99; $fsa[26]['ST']  = 99; $fsa[27]['ST']  = 99; $fsa[28]['ST']  = 99; $fsa[29]['ST']  = 99;
        $fsa[25]['BGN'] = 99; $fsa[26]['BGN'] = 99; $fsa[27]['BGN'] = 99; $fsa[28]['BGN'] = 99; $fsa[29]['BGN'] = 99;
        $fsa[25]['N1']  = 25; $fsa[26]['N1']  = 99; $fsa[27]['N1']  = 99; $fsa[28]['N1']  = 99; $fsa[29]['N1']  = 99;
        $fsa[25]['N2']  = 99; $fsa[26]['N2']  = 99; $fsa[27]['N2']  = 99; $fsa[28]['N2']  = 99; $fsa[29]['N2']  = 99;
        $fsa[25]['N3']  = 40; $fsa[26]['N3']  = 99; $fsa[27]['N3']  = 99; $fsa[28]['N3']  = 99; $fsa[29]['N3']  = 99;
        $fsa[25]['N4']  = 41; $fsa[26]['N4']  = 99; $fsa[27]['N4']  = 99; $fsa[28]['N4']  = 99; $fsa[29]['N4']  = 47;
        $fsa[25]['PER'] = 99; $fsa[26]['PER'] = 99; $fsa[27]['PER'] = 99; $fsa[28]['PER'] = 99; $fsa[29]['PER'] = 99;
        $fsa[25]['REF'] = 99; $fsa[26]['REF'] = 99; $fsa[27]['REF'] = 99; $fsa[28]['REF'] = 99; $fsa[29]['REF'] = 50;
        $fsa[25]['DTP'] = 99; $fsa[26]['DTP'] = 43; $fsa[27]['DTP'] = 99; $fsa[28]['DTP'] = 99; $fsa[29]['DTP'] = 48;
        $fsa[25]['IN1'] = 99; $fsa[26]['IN1'] = 99; $fsa[27]['IN1'] = 99; $fsa[28]['IN1'] = 99; $fsa[29]['IN1'] = 99;
        $fsa[25]['IN2'] = 99; $fsa[26]['IN2'] = 99; $fsa[27]['IN2'] = 99; $fsa[28]['IN2'] = 99; $fsa[29]['IN2'] = 99;
        $fsa[25]['DMG'] = 99; $fsa[26]['DMG'] = 99; $fsa[27]['DMG'] = 99; $fsa[28]['DMG'] = 99; $fsa[29]['DMG'] = 99;
        $fsa[25]['IND'] = 99; $fsa[26]['IND'] = 99; $fsa[27]['IND'] = 99; $fsa[28]['IND'] = 99; $fsa[29]['IND'] = 99;
        $fsa[25]['IMM'] = 99; $fsa[26]['IMM'] = 99; $fsa[27]['IMM'] = 99; $fsa[28]['IMM'] = 99; $fsa[29]['IMM'] = 99;
        $fsa[25]['LUI'] = 99; $fsa[26]['LUI'] = 99; $fsa[27]['LUI'] = 99; $fsa[28]['LUI'] = 99; $fsa[29]['LUI'] = 99;
        $fsa[25]['III'] = 99; $fsa[26]['III'] = 99; $fsa[27]['III'] = 99; $fsa[28]['III'] = 99; $fsa[29]['III'] = 99;
        $fsa[25]['NTE'] = 99; $fsa[26]['NTE'] = 99; $fsa[27]['NTE'] = 99; $fsa[28]['NTE'] = 99; $fsa[29]['NTE'] = 99;
        $fsa[25]['COM'] = 99; $fsa[26]['COM'] = 99; $fsa[27]['COM'] = 99; $fsa[28]['COM'] = 99; $fsa[29]['COM'] = 99;
        $fsa[25]['EMS'] = 42; $fsa[26]['EMS'] = 99; $fsa[27]['EMS'] = 99; $fsa[28]['EMS'] = 99; $fsa[29]['EMS'] = 99;
        $fsa[25]['QTY'] = 99; $fsa[26]['QTY'] = 99; $fsa[27]['QTY'] = 99; $fsa[28]['QTY'] = 99; $fsa[29]['QTY'] = 49;
        $fsa[25]['ATV'] = 26; $fsa[26]['ATV'] = 26; $fsa[27]['ATV'] = 99; $fsa[28]['ATV'] = 99; $fsa[29]['ATV'] = 99;
        $fsa[25]['AMT'] = 27; $fsa[26]['AMT'] = 27; $fsa[27]['AMT'] = 27; $fsa[28]['AMT'] = 99; $fsa[29]['AMT'] = 99;
        $fsa[25]['MSG'] = 99; $fsa[26]['MSG'] = 99; $fsa[27]['MSG'] = 44; $fsa[28]['MSG'] = 99; $fsa[29]['MSG'] = 99;
        $fsa[25]['SSE'] = 28; $fsa[26]['SSE'] = 28; $fsa[27]['SSE'] = 28; $fsa[28]['SSE'] = 28; $fsa[29]['SSE'] = 99;
        $fsa[25]['DEG'] = 99; $fsa[26]['DEG'] = 99; $fsa[27]['DEG'] = 99; $fsa[28]['DEG'] = 45; $fsa[29]['DEG'] = 99;
        $fsa[25]['FOS'] = 99; $fsa[26]['FOS'] = 99; $fsa[27]['FOS'] = 99; $fsa[28]['FOS'] = 46; $fsa[29]['FOS'] = 99;
        $fsa[25]['RSD'] = 29; $fsa[26]['RSD'] = 29; $fsa[27]['RSD'] = 29; $fsa[28]['RSD'] = 29; $fsa[29]['RSD'] = 29;
        $fsa[25]['RQS'] = 30; $fsa[26]['RQS'] = 30; $fsa[27]['RQS'] = 30; $fsa[28]['RQS'] = 30; $fsa[29]['RQS'] = 30;
        $fsa[25]['SST'] = 31; $fsa[26]['SST'] = 31; $fsa[27]['SST'] = 31; $fsa[28]['SST'] = 31; $fsa[29]['SST'] = 31;
        $fsa[25]['SUM'] = 99; $fsa[26]['SUM'] = 99; $fsa[27]['SUM'] = 99; $fsa[28]['SUM'] = 99; $fsa[29]['SUM'] = 99;
        $fsa[25]['SES'] = 99; $fsa[26]['SES'] = 99; $fsa[27]['SES'] = 99; $fsa[28]['SES'] = 99; $fsa[29]['SES'] = 99;
        $fsa[25]['CRS'] = 99; $fsa[26]['CRS'] = 99; $fsa[27]['CRS'] = 99; $fsa[28]['CRS'] = 99; $fsa[29]['CRS'] = 99;
        $fsa[25]['TST'] = 32; $fsa[26]['TST'] = 32; $fsa[27]['TST'] = 32; $fsa[28]['TST'] = 32; $fsa[29]['TST'] = 32;
        $fsa[25]['SBT'] = 99; $fsa[26]['SBT'] = 99; $fsa[27]['SBT'] = 99; $fsa[28]['SBT'] = 99; $fsa[29]['SBT'] = 99;
        $fsa[25]['SRE'] = 99; $fsa[26]['SRE'] = 99; $fsa[27]['SRE'] = 99; $fsa[28]['SRE'] = 99; $fsa[29]['SRE'] = 99;
        $fsa[25]['PCL'] = 33; $fsa[26]['PCL'] = 33; $fsa[27]['PCL'] = 33; $fsa[28]['PCL'] = 33; $fsa[29]['PCL'] = 33;
        $fsa[25]['LX']  = 34; $fsa[26]['LX']  = 34; $fsa[27]['LX']  = 34; $fsa[28]['LX']  = 34; $fsa[29]['LX']  = 34;
        $fsa[25]['LT']  = 35; $fsa[26]['LT']  = 35; $fsa[27]['LT']  = 35; $fsa[28]['LT']  = 35; $fsa[29]['LT']  = 35;
        $fsa[25]['LTE'] = 99; $fsa[26]['LTE'] = 99; $fsa[27]['LTE'] = 99; $fsa[28]['LTE'] = 99; $fsa[29]['LTE'] = 99;
        $fsa[25]['SE']  = 36; $fsa[26]['SE']  = 36; $fsa[27]['SE']  = 36; $fsa[28]['SE']  = 36; $fsa[29]['SE']  = 36;
        $fsa[25]['GE']  = 99; $fsa[26]['GE']  = 99; $fsa[27]['GE']  = 99; $fsa[28]['GE']  = 99; $fsa[29]['GE']  = 99;
        $fsa[25]['IEA'] = 99; $fsa[26]['IEA'] = 99; $fsa[27]['IEA'] = 99; $fsa[28]['IEA'] = 99; $fsa[29]['IEA'] = 99;

        $fsa[30]['ISA'] = 99; $fsa[31]['ISA'] = 99; $fsa[32]['ISA'] = 99; $fsa[33]['ISA'] = 99; $fsa[34]['ISA'] = 99;
        $fsa[30]['GS']  = 99; $fsa[31]['GS']  = 99; $fsa[32]['GS']  = 99; $fsa[33]['GS']  = 99; $fsa[34]['GS']  = 99;
        $fsa[30]['ST']  = 99; $fsa[31]['ST']  = 99; $fsa[32]['ST']  = 99; $fsa[33]['ST']  = 99; $fsa[34]['ST']  = 99;
        $fsa[30]['BGN'] = 99; $fsa[31]['BGN'] = 99; $fsa[32]['BGN'] = 99; $fsa[33]['BGN'] = 99; $fsa[34]['BGN'] = 99;
        $fsa[30]['N1']  = 99; $fsa[31]['N1']  = 53; $fsa[32]['N1']  = 99; $fsa[33]['N1']  = 99; $fsa[34]['N1']  = 99;
        $fsa[30]['N2']  = 99; $fsa[31]['N2']  = 99; $fsa[32]['N2']  = 99; $fsa[33]['N2']  = 99; $fsa[34]['N2']  = 99;
        $fsa[30]['N3']  = 99; $fsa[31]['N3']  = 54; $fsa[32]['N3']  = 99; $fsa[33]['N3']  = 59; $fsa[34]['N3']  = 99;
        $fsa[30]['N4']  = 99; $fsa[31]['N4']  = 55; $fsa[32]['N4']  = 99; $fsa[33]['N4']  = 60; $fsa[34]['N4']  = 99;
        $fsa[30]['PER'] = 99; $fsa[31]['PER'] = 99; $fsa[32]['PER'] = 99; $fsa[33]['PER'] = 99; $fsa[34]['PER'] = 99;
        $fsa[30]['REF'] = 99; $fsa[31]['REF'] = 99; $fsa[32]['REF'] = 99; $fsa[33]['REF'] = 99; $fsa[34]['REF'] = 99;
        $fsa[30]['DTP'] = 99; $fsa[31]['DTP'] = 99; $fsa[32]['DTP'] = 99; $fsa[33]['DTP'] = 99; $fsa[34]['DTP'] = 99;
        $fsa[30]['IN1'] = 99; $fsa[31]['IN1'] = 99; $fsa[32]['IN1'] = 99; $fsa[33]['IN1'] = 99; $fsa[34]['IN1'] = 99;
        $fsa[30]['IN2'] = 99; $fsa[31]['IN2'] = 99; $fsa[32]['IN2'] = 99; $fsa[33]['IN2'] = 99; $fsa[34]['IN2'] = 99;
        $fsa[30]['DMG'] = 99; $fsa[31]['DMG'] = 99; $fsa[32]['DMG'] = 99; $fsa[33]['DMG'] = 99; $fsa[34]['DMG'] = 99;
        $fsa[30]['IND'] = 99; $fsa[31]['IND'] = 99; $fsa[32]['IND'] = 99; $fsa[33]['IND'] = 99; $fsa[34]['IND'] = 99;
        $fsa[30]['IMM'] = 99; $fsa[31]['IMM'] = 99; $fsa[32]['IMM'] = 99; $fsa[33]['IMM'] = 99; $fsa[34]['IMM'] = 99;
        $fsa[30]['LUI'] = 99; $fsa[31]['LUI'] = 99; $fsa[32]['LUI'] = 99; $fsa[33]['LUI'] = 99; $fsa[34]['LUI'] = 99;
        $fsa[30]['III'] = 99; $fsa[31]['III'] = 99; $fsa[32]['III'] = 99; $fsa[33]['III'] = 99; $fsa[34]['III'] = 99;
        $fsa[30]['NTE'] = 99; $fsa[31]['NTE'] = 99; $fsa[32]['NTE'] = 99; $fsa[33]['NTE'] = 99; $fsa[34]['NTE'] = 99;
        $fsa[30]['COM'] = 99; $fsa[31]['COM'] = 99; $fsa[32]['COM'] = 99; $fsa[33]['COM'] = 99; $fsa[34]['COM'] = 99;
        $fsa[30]['EMS'] = 99; $fsa[31]['EMS'] = 99; $fsa[32]['EMS'] = 99; $fsa[33]['EMS'] = 99; $fsa[34]['EMS'] = 99;
        $fsa[30]['QTY'] = 99; $fsa[31]['QTY'] = 99; $fsa[32]['QTY'] = 99; $fsa[33]['QTY'] = 99; $fsa[34]['QTY'] = 99;
        $fsa[30]['ATV'] = 99; $fsa[31]['ATV'] = 99; $fsa[32]['ATV'] = 99; $fsa[33]['ATV'] = 99; $fsa[34]['ATV'] = 99;
        $fsa[30]['AMT'] = 99; $fsa[31]['AMT'] = 99; $fsa[32]['AMT'] = 99; $fsa[33]['AMT'] = 99; $fsa[34]['AMT'] = 99;
        $fsa[30]['MSG'] = 51; $fsa[31]['MSG'] = 99; $fsa[32]['MSG'] = 99; $fsa[33]['MSG'] = 99; $fsa[34]['MSG'] = 64;
        $fsa[30]['SSE'] = 99; $fsa[31]['SSE'] = 52; $fsa[32]['SSE'] = 99; $fsa[33]['SSE'] = 61; $fsa[34]['SSE'] = 99;
        $fsa[30]['DEG'] = 99; $fsa[31]['DEG'] = 99; $fsa[32]['DEG'] = 99; $fsa[33]['DEG'] = 99; $fsa[34]['DEG'] = 99;
        $fsa[30]['FOS'] = 99; $fsa[31]['FOS'] = 99; $fsa[32]['FOS'] = 99; $fsa[33]['FOS'] = 99; $fsa[34]['FOS'] = 99;
        $fsa[30]['RSD'] = 99; $fsa[31]['RSD'] = 99; $fsa[32]['RSD'] = 99; $fsa[33]['RSD'] = 99; $fsa[34]['RSD'] = 99;
        $fsa[30]['RQS'] = 30; $fsa[31]['RQS'] = 99; $fsa[32]['RQS'] = 99; $fsa[33]['RQS'] = 99; $fsa[34]['RQS'] = 99;
        $fsa[30]['SST'] = 31; $fsa[31]['SST'] = 31; $fsa[32]['SST'] = 99; $fsa[33]['SST'] = 99; $fsa[34]['SST'] = 99;
        $fsa[30]['SUM'] = 99; $fsa[31]['SUM'] = 56; $fsa[32]['SUM'] = 99; $fsa[33]['SUM'] = 62; $fsa[34]['SUM'] = 99;
        $fsa[30]['SES'] = 99; $fsa[31]['SES'] = 57; $fsa[32]['SES'] = 99; $fsa[33]['SES'] = 63; $fsa[34]['SES'] = 99;
        $fsa[30]['CRS'] = 99; $fsa[31]['CRS'] = 99; $fsa[32]['CRS'] = 99; $fsa[33]['CRS'] = 99; $fsa[34]['CRS'] = 99;
        $fsa[30]['TST'] = 32; $fsa[31]['TST'] = 32; $fsa[32]['TST'] = 32; $fsa[33]['TST'] = 99; $fsa[34]['TST'] = 99;
        $fsa[30]['SBT'] = 99; $fsa[31]['SBT'] = 99; $fsa[32]['SBT'] = 58; $fsa[33]['SBT'] = 99; $fsa[34]['SBT'] = 99;
        $fsa[30]['SRE'] = 99; $fsa[31]['SRE'] = 99; $fsa[32]['SRE'] = 99; $fsa[33]['SRE'] = 99; $fsa[34]['SRE'] = 99;
        $fsa[30]['PCL'] = 33; $fsa[31]['PCL'] = 33; $fsa[32]['PCL'] = 33; $fsa[33]['PCL'] = 33; $fsa[34]['PCL'] = 99;
        $fsa[30]['LX']  = 34; $fsa[31]['LX']  = 34; $fsa[32]['LX']  = 34; $fsa[33]['LX']  = 34; $fsa[34]['LX']  = 99;
        $fsa[30]['LT']  = 35; $fsa[31]['LT']  = 35; $fsa[32]['LT']  = 35; $fsa[33]['LT']  = 35; $fsa[34]['LT']  = 35;
        $fsa[30]['LTE'] = 99; $fsa[31]['LTE'] = 99; $fsa[32]['LTE'] = 99; $fsa[33]['LTE'] = 99; $fsa[34]['LTE'] = 99;
        $fsa[30]['SE']  = 36; $fsa[31]['SE']  = 36; $fsa[32]['SE']  = 36; $fsa[33]['SE']  = 36; $fsa[34]['SE']  = 36;
        $fsa[30]['GE']  = 99; $fsa[31]['GE']  = 99; $fsa[32]['GE']  = 99; $fsa[33]['GE']  = 99; $fsa[34]['GE']  = 99;
        $fsa[30]['IEA'] = 99; $fsa[31]['IEA'] = 99; $fsa[32]['IEA'] = 99; $fsa[33]['IEA'] = 99; $fsa[34]['IEA'] = 99;

        $fsa[35]['ISA'] = 99; $fsa[36]['ISA'] = 99; $fsa[37]['ISA'] = 99; $fsa[38]['ISA'] = 99; $fsa[39]['ISA'] = 99;
        $fsa[35]['GS']  = 99; $fsa[36]['GS']  = 99; $fsa[37]['GS']  = 99; $fsa[38]['GS']  = 99; $fsa[39]['GS']  = 99;
        $fsa[35]['ST']  = 99; $fsa[36]['ST']  = 3;  $fsa[37]['ST']  = 99; $fsa[38]['ST']  = 99; $fsa[39]['ST']  = 99;
        $fsa[35]['BGN'] = 99; $fsa[36]['BGN'] = 99; $fsa[37]['BGN'] = 99; $fsa[38]['BGN'] = 99; $fsa[39]['BGN'] = 99;
        $fsa[35]['N1']  = 68; $fsa[36]['N1']  = 99; $fsa[37]['N1']  = 25; $fsa[38]['N1']  = 25; $fsa[39]['N1']  = 25;
        $fsa[35]['N2']  = 99; $fsa[36]['N2']  = 99; $fsa[37]['N2']  = 99; $fsa[38]['N2']  = 99; $fsa[39]['N2']  = 99;
        $fsa[35]['N3']  = 69; $fsa[36]['N3']  = 99; $fsa[37]['N3']  = 23; $fsa[38]['N3']  = 23; $fsa[39]['N3']  = 99;
        $fsa[35]['N4']  = 70; $fsa[36]['N4']  = 99; $fsa[37]['N4']  = 99; $fsa[38]['N4']  = 99; $fsa[39]['N4']  = 99;
        $fsa[35]['PER'] = 99; $fsa[36]['PER'] = 99; $fsa[37]['PER'] = 99; $fsa[38]['PER'] = 99; $fsa[39]['PER'] = 99;
        $fsa[35]['REF'] = 99; $fsa[36]['REF'] = 99; $fsa[37]['REF'] = 99; $fsa[38]['REF'] = 99; $fsa[39]['REF'] = 99;
        $fsa[35]['DTP'] = 65; $fsa[36]['DTP'] = 99; $fsa[37]['DTP'] = 38; $fsa[38]['DTP'] = 38; $fsa[39]['DTP'] = 39;
        $fsa[35]['IN1'] = 99; $fsa[36]['IN1'] = 99; $fsa[37]['IN1'] = 14; $fsa[38]['IN1'] = 14; $fsa[39]['IN1'] = 14;
        $fsa[35]['IN2'] = 99; $fsa[36]['IN2'] = 99; $fsa[37]['IN2'] = 99; $fsa[38]['IN2'] = 99; $fsa[39]['IN2'] = 99;
        $fsa[35]['DMG'] = 99; $fsa[36]['DMG'] = 99; $fsa[37]['DMG'] = 99; $fsa[38]['DMG'] = 99; $fsa[39]['DMG'] = 99;
        $fsa[35]['IND'] = 99; $fsa[36]['IND'] = 99; $fsa[37]['IND'] = 99; $fsa[38]['IND'] = 99; $fsa[39]['IND'] = 99;
        $fsa[35]['IMM'] = 99; $fsa[36]['IMM'] = 99; $fsa[37]['IMM'] = 99; $fsa[38]['IMM'] = 99; $fsa[39]['IMM'] = 99;
        $fsa[35]['LUI'] = 99; $fsa[36]['LUI'] = 99; $fsa[37]['LUI'] = 99; $fsa[38]['LUI'] = 99; $fsa[39]['LUI'] = 99;
        $fsa[35]['III'] = 99; $fsa[36]['III'] = 99; $fsa[37]['III'] = 99; $fsa[38]['III'] = 99; $fsa[39]['III'] = 99;
        $fsa[35]['NTE'] = 99; $fsa[36]['NTE'] = 99; $fsa[37]['NTE'] = 99; $fsa[38]['NTE'] = 99; $fsa[39]['NTE'] = 99;
        $fsa[35]['COM'] = 67; $fsa[36]['COM'] = 99; $fsa[37]['COM'] = 24; $fsa[38]['COM'] = 24; $fsa[39]['COM'] = 24;
        $fsa[35]['EMS'] = 99; $fsa[36]['EMS'] = 99; $fsa[37]['EMS'] = 99; $fsa[38]['EMS'] = 99; $fsa[39]['EMS'] = 99;
        $fsa[35]['QTY'] = 66; $fsa[36]['QTY'] = 99; $fsa[37]['QTY'] = 99; $fsa[38]['QTY'] = 99; $fsa[39]['QTY'] = 99;
        $fsa[35]['ATV'] = 99; $fsa[36]['ATV'] = 99; $fsa[37]['ATV'] = 26; $fsa[38]['ATV'] = 26; $fsa[39]['ATV'] = 26;
        $fsa[35]['AMT'] = 99; $fsa[36]['AMT'] = 99; $fsa[37]['AMT'] = 27; $fsa[38]['AMT'] = 27; $fsa[39]['AMT'] = 27;
        $fsa[35]['MSG'] = 72; $fsa[36]['MSG'] = 99; $fsa[37]['MSG'] = 99; $fsa[38]['MSG'] = 99; $fsa[39]['MSG'] = 99;
        $fsa[35]['SSE'] = 99; $fsa[36]['SSE'] = 99; $fsa[37]['SSE'] = 28; $fsa[38]['SSE'] = 28; $fsa[39]['SSE'] = 28;
        $fsa[35]['DEG'] = 99; $fsa[36]['DEG'] = 99; $fsa[37]['DEG'] = 99; $fsa[38]['DEG'] = 99; $fsa[39]['DEG'] = 99;
        $fsa[35]['FOS'] = 99; $fsa[36]['FOS'] = 99; $fsa[37]['FOS'] = 99; $fsa[38]['FOS'] = 99; $fsa[39]['FOS'] = 99;
        $fsa[35]['RSD'] = 99; $fsa[36]['RSD'] = 99; $fsa[37]['RSD'] = 29; $fsa[38]['RSD'] = 29; $fsa[39]['RSD'] = 29;
        $fsa[35]['RQS'] = 99; $fsa[36]['RQS'] = 99; $fsa[37]['RQS'] = 30; $fsa[38]['RQS'] = 30; $fsa[39]['RQS'] = 30;
        $fsa[35]['SST'] = 99; $fsa[36]['SST'] = 99; $fsa[37]['SST'] = 31; $fsa[38]['SST'] = 31; $fsa[39]['SST'] = 31;
        $fsa[35]['SUM'] = 99; $fsa[36]['SUM'] = 99; $fsa[37]['SUM'] = 99; $fsa[38]['SUM'] = 99; $fsa[39]['SUM'] = 99;
        $fsa[35]['SES'] = 99; $fsa[36]['SES'] = 99; $fsa[37]['SES'] = 99; $fsa[38]['SES'] = 99; $fsa[39]['SES'] = 99;
        $fsa[35]['CRS'] = 99; $fsa[36]['CRS'] = 99; $fsa[37]['CRS'] = 99; $fsa[38]['CRS'] = 99; $fsa[39]['CRS'] = 99;
        $fsa[35]['TST'] = 99; $fsa[36]['TST'] = 99; $fsa[37]['TST'] = 32; $fsa[38]['TST'] = 32; $fsa[39]['TST'] = 32;
        $fsa[35]['SBT'] = 99; $fsa[36]['SBT'] = 99; $fsa[37]['SBT'] = 99; $fsa[38]['SBT'] = 99; $fsa[39]['SBT'] = 99;
        $fsa[35]['SRE'] = 99; $fsa[36]['SRE'] = 99; $fsa[37]['SRE'] = 99; $fsa[38]['SRE'] = 99; $fsa[39]['SRE'] = 99;
        $fsa[35]['PCL'] = 99; $fsa[36]['PCL'] = 99; $fsa[37]['PCL'] = 33; $fsa[38]['PCL'] = 33; $fsa[39]['PCL'] = 33;
        $fsa[35]['LX']  = 99; $fsa[36]['LX']  = 99; $fsa[37]['LX']  = 34; $fsa[38]['LX']  = 34; $fsa[39]['LX']  = 34;
        $fsa[35]['LT']  = 35; $fsa[36]['LT']  = 99; $fsa[37]['LT']  = 35; $fsa[38]['LT']  = 35; $fsa[39]['LT']  = 35;
        $fsa[35]['LTE'] = 71; $fsa[36]['LTE'] = 99; $fsa[37]['LTE'] = 99; $fsa[38]['LTE'] = 99; $fsa[39]['LTE'] = 99;
        $fsa[35]['SE']  = 36; $fsa[36]['SE']  = 99; $fsa[37]['SE']  = 36; $fsa[38]['SE']  = 36; $fsa[39]['SE']  = 36;
        $fsa[35]['GE']  = 99; $fsa[36]['GE']  = 73; $fsa[37]['GE']  = 99; $fsa[38]['GE']  = 99; $fsa[39]['GE']  = 99;
        $fsa[35]['IEA'] = 99; $fsa[36]['IEA'] = 99; $fsa[37]['IEA'] = 99; $fsa[38]['IEA'] = 99; $fsa[39]['IEA'] = 99;

        $fsa[40]['ISA'] = 99; $fsa[41]['ISA'] = 99; $fsa[42]['ISA'] = 99; $fsa[43]['ISA'] = 99; $fsa[44]['ISA'] = 99;
        $fsa[40]['GS']  = 99; $fsa[41]['GS']  = 99; $fsa[42]['GS']  = 99; $fsa[43]['GS']  = 99; $fsa[44]['GS']  = 99;
        $fsa[40]['ST']  = 99; $fsa[41]['ST']  = 99; $fsa[42]['ST']  = 99; $fsa[43]['ST']  = 99; $fsa[44]['ST']  = 99;
        $fsa[40]['BGN'] = 99; $fsa[41]['BGN'] = 99; $fsa[42]['BGN'] = 99; $fsa[43]['BGN'] = 99; $fsa[44]['BGN'] = 99;
        $fsa[40]['N1']  = 25; $fsa[41]['N1']  = 99; $fsa[42]['N1']  = 25; $fsa[43]['N1']  = 99; $fsa[44]['N1']  = 99;
        $fsa[40]['N2']  = 99; $fsa[41]['N2']  = 99; $fsa[42]['N2']  = 99; $fsa[43]['N2']  = 99; $fsa[44]['N2']  = 99;
        $fsa[40]['N3']  = 99; $fsa[41]['N3']  = 99; $fsa[42]['N3']  = 99; $fsa[43]['N3']  = 99; $fsa[44]['N3']  = 99;
        $fsa[40]['N4']  = 37; $fsa[41]['N4']  = 99; $fsa[42]['N4']  = 99; $fsa[43]['N4']  = 99; $fsa[44]['N4']  = 99;
        $fsa[40]['PER'] = 99; $fsa[41]['PER'] = 99; $fsa[42]['PER'] = 99; $fsa[43]['PER'] = 99; $fsa[44]['PER'] = 99;
        $fsa[40]['REF'] = 99; $fsa[41]['REF'] = 99; $fsa[42]['REF'] = 99; $fsa[43]['REF'] = 99; $fsa[44]['REF'] = 99;
        $fsa[40]['DTP'] = 99; $fsa[41]['DTP'] = 99; $fsa[42]['DTP'] = 74; $fsa[43]['DTP'] = 43; $fsa[44]['DTP'] = 99;
        $fsa[40]['IN1'] = 14; $fsa[41]['IN1'] = 14; $fsa[42]['IN1'] = 14; $fsa[43]['IN1'] = 99; $fsa[44]['IN1'] = 99;
        $fsa[40]['IN2'] = 99; $fsa[41]['IN2'] = 99; $fsa[42]['IN2'] = 99; $fsa[43]['IN2'] = 99; $fsa[44]['IN2'] = 99;
        $fsa[40]['DMG'] = 99; $fsa[41]['DMG'] = 99; $fsa[42]['DMG'] = 99; $fsa[43]['DMG'] = 99; $fsa[44]['DMG'] = 99;
        $fsa[40]['IND'] = 99; $fsa[41]['IND'] = 99; $fsa[42]['IND'] = 99; $fsa[43]['IND'] = 99; $fsa[44]['IND'] = 99;
        $fsa[40]['IMM'] = 99; $fsa[41]['IMM'] = 99; $fsa[42]['IMM'] = 99; $fsa[43]['IMM'] = 99; $fsa[44]['IMM'] = 99;
        $fsa[40]['LUI'] = 99; $fsa[41]['LUI'] = 99; $fsa[42]['LUI'] = 99; $fsa[43]['LUI'] = 99; $fsa[44]['LUI'] = 99;
        $fsa[40]['III'] = 99; $fsa[41]['III'] = 99; $fsa[42]['III'] = 99; $fsa[43]['III'] = 99; $fsa[44]['III'] = 99;
        $fsa[40]['NTE'] = 99; $fsa[41]['NTE'] = 99; $fsa[42]['NTE'] = 99; $fsa[43]['NTE'] = 99; $fsa[44]['NTE'] = 99;
        $fsa[40]['COM'] = 99; $fsa[41]['COM'] = 99; $fsa[42]['COM'] = 99; $fsa[43]['COM'] = 99; $fsa[44]['COM'] = 99;
        $fsa[40]['EMS'] = 42; $fsa[41]['EMS'] = 42; $fsa[42]['EMS'] = 42; $fsa[43]['EMS'] = 99; $fsa[44]['EMS'] = 99;
        $fsa[40]['QTY'] = 99; $fsa[41]['QTY'] = 99; $fsa[42]['QTY'] = 75; $fsa[43]['QTY'] = 99; $fsa[44]['QTY'] = 99;
        $fsa[40]['ATV'] = 26; $fsa[41]['ATV'] = 26; $fsa[42]['ATV'] = 26; $fsa[43]['ATV'] = 99; $fsa[44]['ATV'] = 99;
        $fsa[40]['AMT'] = 27; $fsa[41]['AMT'] = 27; $fsa[42]['AMT'] = 27; $fsa[43]['AMT'] = 27; $fsa[44]['AMT'] = 27;
        $fsa[40]['MSG'] = 99; $fsa[41]['MSG'] = 99; $fsa[42]['MSG'] = 99; $fsa[43]['MSG'] = 99; $fsa[44]['MSG'] = 99;
        $fsa[40]['SSE'] = 28; $fsa[41]['SSE'] = 28; $fsa[42]['SSE'] = 28; $fsa[43]['SSE'] = 28; $fsa[44]['SSE'] = 28;
        $fsa[40]['DEG'] = 99; $fsa[41]['DEG'] = 99; $fsa[42]['DEG'] = 99; $fsa[43]['DEG'] = 99; $fsa[44]['DEG'] = 99;
        $fsa[40]['FOS'] = 99; $fsa[41]['FOS'] = 99; $fsa[42]['FOS'] = 99; $fsa[43]['FOS'] = 99; $fsa[44]['FOS'] = 99;
        $fsa[40]['RSD'] = 29; $fsa[41]['RSD'] = 29; $fsa[42]['RSD'] = 29; $fsa[43]['RSD'] = 29; $fsa[44]['RSD'] = 29;
        $fsa[40]['RQS'] = 30; $fsa[41]['RQS'] = 30; $fsa[42]['RQS'] = 30; $fsa[43]['RQS'] = 30; $fsa[44]['RQS'] = 30;
        $fsa[40]['SST'] = 31; $fsa[41]['SST'] = 31; $fsa[42]['SST'] = 31; $fsa[43]['SST'] = 31; $fsa[44]['SST'] = 31;
        $fsa[40]['SUM'] = 99; $fsa[41]['SUM'] = 99; $fsa[42]['SUM'] = 99; $fsa[43]['SUM'] = 99; $fsa[44]['SUM'] = 99;
        $fsa[40]['SES'] = 99; $fsa[41]['SES'] = 99; $fsa[42]['SES'] = 99; $fsa[43]['SES'] = 99; $fsa[44]['SES'] = 99;
        $fsa[40]['CRS'] = 99; $fsa[41]['CRS'] = 99; $fsa[42]['CRS'] = 99; $fsa[43]['CRS'] = 99; $fsa[44]['CRS'] = 99;
        $fsa[40]['TST'] = 32; $fsa[41]['TST'] = 32; $fsa[42]['TST'] = 32; $fsa[43]['TST'] = 32; $fsa[44]['TST'] = 32;
        $fsa[40]['SBT'] = 99; $fsa[41]['SBT'] = 99; $fsa[42]['SBT'] = 99; $fsa[43]['SBT'] = 99; $fsa[44]['SBT'] = 99;
        $fsa[40]['SRE'] = 99; $fsa[41]['SRE'] = 99; $fsa[42]['SRE'] = 99; $fsa[43]['SRE'] = 99; $fsa[44]['SRE'] = 99;
        $fsa[40]['PCL'] = 33; $fsa[41]['PCL'] = 33; $fsa[42]['PCL'] = 33; $fsa[43]['PCL'] = 33; $fsa[44]['PCL'] = 33;
        $fsa[40]['LX']  = 34; $fsa[41]['LX']  = 34; $fsa[42]['LX']  = 34; $fsa[43]['LX']  = 34; $fsa[44]['LX']  = 34;
        $fsa[40]['LT']  = 35; $fsa[41]['LT']  = 35; $fsa[42]['LT']  = 35; $fsa[43]['LT']  = 35; $fsa[44]['LT']  = 35;
        $fsa[40]['LTE'] = 99; $fsa[41]['LTE'] = 99; $fsa[42]['LTE'] = 99; $fsa[43]['LTE'] = 99; $fsa[44]['LTE'] = 99;
        $fsa[40]['SE']  = 36; $fsa[41]['SE']  = 36; $fsa[42]['SE']  = 36; $fsa[43]['SE']  = 36; $fsa[44]['SE']  = 36;
        $fsa[40]['GE']  = 99; $fsa[41]['GE']  = 99; $fsa[42]['GE']  = 99; $fsa[43]['GE']  = 99; $fsa[44]['GE']  = 99;
        $fsa[40]['IEA'] = 99; $fsa[41]['IEA'] = 99; $fsa[42]['IEA'] = 99; $fsa[43]['IEA'] = 99; $fsa[44]['IEA'] = 99;

        $fsa[45]['ISA'] = 99; $fsa[46]['ISA'] = 99; $fsa[47]['ISA'] = 99; $fsa[48]['ISA'] = 99; $fsa[49]['ISA'] = 99;
        $fsa[45]['GS']  = 99; $fsa[46]['GS']  = 99; $fsa[47]['GS']  = 99; $fsa[48]['GS']  = 99; $fsa[49]['GS']  = 99;
        $fsa[45]['ST']  = 99; $fsa[46]['ST']  = 99; $fsa[47]['ST']  = 99; $fsa[48]['ST']  = 99; $fsa[49]['ST']  = 99;
        $fsa[45]['BGN'] = 99; $fsa[46]['BGN'] = 99; $fsa[47]['BGN'] = 99; $fsa[48]['BGN'] = 99; $fsa[49]['BGN'] = 99;
        $fsa[45]['N1']  = 99; $fsa[46]['N1']  = 99; $fsa[47]['N1']  = 99; $fsa[48]['N1']  = 99; $fsa[49]['N1']  = 99;
        $fsa[45]['N2']  = 99; $fsa[46]['N2']  = 99; $fsa[47]['N2']  = 99; $fsa[48]['N2']  = 99; $fsa[49]['N2']  = 99;
        $fsa[45]['N3']  = 99; $fsa[46]['N3']  = 99; $fsa[47]['N3']  = 99; $fsa[48]['N3']  = 99; $fsa[49]['N3']  = 99;
        $fsa[45]['N4']  = 99; $fsa[46]['N4']  = 99; $fsa[47]['N4']  = 99; $fsa[48]['N4']  = 99; $fsa[49]['N4']  = 99;
        $fsa[45]['PER'] = 99; $fsa[46]['PER'] = 99; $fsa[47]['PER'] = 99; $fsa[48]['PER'] = 99; $fsa[49]['PER'] = 99;
        $fsa[45]['REF'] = 99; $fsa[46]['REF'] = 99; $fsa[47]['REF'] = 50; $fsa[48]['REF'] = 50; $fsa[49]['REF'] = 50;
        $fsa[45]['DTP'] = 99; $fsa[46]['DTP'] = 99; $fsa[47]['DTP'] = 48; $fsa[48]['DTP'] = 48; $fsa[49]['DTP'] = 99;
        $fsa[45]['IN1'] = 99; $fsa[46]['IN1'] = 99; $fsa[47]['IN1'] = 99; $fsa[48]['IN1'] = 99; $fsa[49]['IN1'] = 99;
        $fsa[45]['IN2'] = 99; $fsa[46]['IN2'] = 99; $fsa[47]['IN2'] = 99; $fsa[48]['IN2'] = 99; $fsa[49]['IN2'] = 99;
        $fsa[45]['DMG'] = 99; $fsa[46]['DMG'] = 99; $fsa[47]['DMG'] = 99; $fsa[48]['DMG'] = 99; $fsa[49]['DMG'] = 99;
        $fsa[45]['IND'] = 99; $fsa[46]['IND'] = 99; $fsa[47]['IND'] = 99; $fsa[48]['IND'] = 99; $fsa[49]['IND'] = 99;
        $fsa[45]['IMM'] = 99; $fsa[46]['IMM'] = 99; $fsa[47]['IMM'] = 99; $fsa[48]['IMM'] = 99; $fsa[49]['IMM'] = 99;
        $fsa[45]['LUI'] = 99; $fsa[46]['LUI'] = 99; $fsa[47]['LUI'] = 99; $fsa[48]['LUI'] = 99; $fsa[49]['LUI'] = 99;
        $fsa[45]['III'] = 99; $fsa[46]['III'] = 99; $fsa[47]['III'] = 99; $fsa[48]['III'] = 99; $fsa[49]['III'] = 99;
        $fsa[45]['NTE'] = 99; $fsa[46]['NTE'] = 99; $fsa[47]['NTE'] = 99; $fsa[48]['NTE'] = 99; $fsa[49]['NTE'] = 99;
        $fsa[45]['COM'] = 99; $fsa[46]['COM'] = 99; $fsa[47]['COM'] = 99; $fsa[48]['COM'] = 99; $fsa[49]['COM'] = 99;
        $fsa[45]['EMS'] = 99; $fsa[46]['EMS'] = 99; $fsa[47]['EMS'] = 99; $fsa[48]['EMS'] = 99; $fsa[49]['EMS'] = 99;
        $fsa[45]['QTY'] = 99; $fsa[46]['QTY'] = 99; $fsa[47]['QTY'] = 49; $fsa[48]['QTY'] = 49; $fsa[49]['QTY'] = 99;
        $fsa[45]['ATV'] = 99; $fsa[46]['ATV'] = 99; $fsa[47]['ATV'] = 99; $fsa[48]['ATV'] = 99; $fsa[49]['ATV'] = 99;
        $fsa[45]['AMT'] = 99; $fsa[46]['AMT'] = 99; $fsa[47]['AMT'] = 99; $fsa[48]['AMT'] = 99; $fsa[49]['AMT'] = 99;
        $fsa[45]['MSG'] = 99; $fsa[46]['MSG'] = 99; $fsa[47]['MSG'] = 99; $fsa[48]['MSG'] = 99; $fsa[49]['MSG'] = 99;
        $fsa[45]['SSE'] = 99; $fsa[46]['SSE'] = 99; $fsa[47]['SSE'] = 99; $fsa[48]['SSE'] = 99; $fsa[49]['SSE'] = 99;
        $fsa[45]['DEG'] = 99; $fsa[46]['DEG'] = 99; $fsa[47]['DEG'] = 99; $fsa[48]['DEG'] = 99; $fsa[49]['DEG'] = 99;
        $fsa[45]['FOS'] = 46; $fsa[46]['FOS'] = 46; $fsa[47]['FOS'] = 99; $fsa[48]['FOS'] = 99; $fsa[49]['FOS'] = 99;
        $fsa[45]['RSD'] = 29; $fsa[46]['RSD'] = 29; $fsa[47]['RSD'] = 29; $fsa[48]['RSD'] = 29; $fsa[49]['RSD'] = 29;
        $fsa[45]['RQS'] = 30; $fsa[46]['RQS'] = 30; $fsa[47]['RQS'] = 30; $fsa[48]['RQS'] = 30; $fsa[49]['RQS'] = 30;
        $fsa[45]['SST'] = 31; $fsa[46]['SST'] = 31; $fsa[47]['SST'] = 31; $fsa[48]['SST'] = 31; $fsa[49]['SST'] = 31;
        $fsa[45]['SUM'] = 99; $fsa[46]['SUM'] = 99; $fsa[47]['SUM'] = 99; $fsa[48]['SUM'] = 99; $fsa[49]['SUM'] = 99;
        $fsa[45]['SES'] = 99; $fsa[46]['SES'] = 99; $fsa[47]['SES'] = 99; $fsa[48]['SES'] = 99; $fsa[49]['SES'] = 99;
        $fsa[45]['CRS'] = 99; $fsa[46]['CRS'] = 99; $fsa[47]['CRS'] = 99; $fsa[48]['CRS'] = 99; $fsa[49]['CRS'] = 99;
        $fsa[45]['TST'] = 32; $fsa[46]['TST'] = 32; $fsa[47]['TST'] = 32; $fsa[48]['TST'] = 32; $fsa[49]['TST'] = 32;
        $fsa[45]['SBT'] = 99; $fsa[46]['SBT'] = 99; $fsa[47]['SBT'] = 99; $fsa[48]['SBT'] = 99; $fsa[49]['SBT'] = 99;
        $fsa[45]['SRE'] = 99; $fsa[46]['SRE'] = 99; $fsa[47]['SRE'] = 99; $fsa[48]['SRE'] = 99; $fsa[49]['SRE'] = 99;
        $fsa[45]['PCL'] = 33; $fsa[46]['PCL'] = 33; $fsa[47]['PCL'] = 33; $fsa[48]['PCL'] = 33; $fsa[49]['PCL'] = 33;
        $fsa[45]['LX']  = 34; $fsa[46]['LX']  = 34; $fsa[47]['LX']  = 34; $fsa[48]['LX']  = 34; $fsa[49]['LX']  = 34;
        $fsa[45]['LT']  = 35; $fsa[46]['LT']  = 35; $fsa[47]['LT']  = 35; $fsa[48]['LT']  = 35; $fsa[49]['LT']  = 35;
        $fsa[45]['LTE'] = 99; $fsa[46]['LTE'] = 99; $fsa[47]['LTE'] = 99; $fsa[48]['LTE'] = 99; $fsa[49]['LTE'] = 99;
        $fsa[45]['SE']  = 36; $fsa[46]['SE']  = 36; $fsa[47]['SE']  = 36; $fsa[48]['SE']  = 36; $fsa[49]['SE']  = 36;
        $fsa[45]['GE']  = 99; $fsa[46]['GE']  = 99; $fsa[47]['GE']  = 99; $fsa[48]['GE']  = 99; $fsa[49]['GE']  = 99;
        $fsa[45]['IEA'] = 99; $fsa[46]['IEA'] = 99; $fsa[47]['IEA'] = 99; $fsa[48]['IEA'] = 99; $fsa[49]['IEA'] = 99;

        $fsa[50]['ISA'] = 99; $fsa[51]['ISA'] = 99; $fsa[52]['ISA'] = 99; $fsa[53]['ISA'] = 99; $fsa[54]['ISA'] = 99;
        $fsa[50]['GS']  = 99; $fsa[51]['GS']  = 99; $fsa[52]['GS']  = 99; $fsa[53]['GS']  = 99; $fsa[54]['GS']  = 99;
        $fsa[50]['ST']  = 99; $fsa[51]['ST']  = 99; $fsa[52]['ST']  = 99; $fsa[53]['ST']  = 99; $fsa[54]['ST']  = 99;
        $fsa[50]['BGN'] = 99; $fsa[51]['BGN'] = 99; $fsa[52]['BGN'] = 99; $fsa[53]['BGN'] = 99; $fsa[54]['BGN'] = 99;
        $fsa[50]['N1']  = 99; $fsa[51]['N1']  = 99; $fsa[52]['N1']  = 53; $fsa[53]['N1']  = 53; $fsa[54]['N1']  = 99;
        $fsa[50]['N2']  = 99; $fsa[51]['N2']  = 99; $fsa[52]['N2']  = 99; $fsa[53]['N2']  = 99; $fsa[54]['N2']  = 99;
        $fsa[50]['N3']  = 99; $fsa[51]['N3']  = 99; $fsa[52]['N3']  = 54; $fsa[53]['N3']  = 54; $fsa[54]['N3']  = 99;
        $fsa[50]['N4']  = 99; $fsa[51]['N4']  = 99; $fsa[52]['N4']  = 55; $fsa[53]['N4']  = 55; $fsa[54]['N4']  = 55;
        $fsa[50]['PER'] = 99; $fsa[51]['PER'] = 99; $fsa[52]['PER'] = 99; $fsa[53]['PER'] = 99; $fsa[54]['PER'] = 99;
        $fsa[50]['REF'] = 99; $fsa[51]['REF'] = 99; $fsa[52]['REF'] = 99; $fsa[53]['REF'] = 99; $fsa[54]['REF'] = 99;
        $fsa[50]['DTP'] = 99; $fsa[51]['DTP'] = 99; $fsa[52]['DTP'] = 99; $fsa[53]['DTP'] = 99; $fsa[54]['DTP'] = 99;
        $fsa[50]['IN1'] = 99; $fsa[51]['IN1'] = 99; $fsa[52]['IN1'] = 99; $fsa[53]['IN1'] = 99; $fsa[54]['IN1'] = 99;
        $fsa[50]['IN2'] = 99; $fsa[51]['IN2'] = 99; $fsa[52]['IN2'] = 99; $fsa[53]['IN2'] = 99; $fsa[54]['IN2'] = 99;
        $fsa[50]['DMG'] = 99; $fsa[51]['DMG'] = 99; $fsa[52]['DMG'] = 99; $fsa[53]['DMG'] = 99; $fsa[54]['DMG'] = 99;
        $fsa[50]['IND'] = 99; $fsa[51]['IND'] = 99; $fsa[52]['IND'] = 99; $fsa[53]['IND'] = 99; $fsa[54]['IND'] = 99;
        $fsa[50]['IMM'] = 99; $fsa[51]['IMM'] = 99; $fsa[52]['IMM'] = 99; $fsa[53]['IMM'] = 99; $fsa[54]['IMM'] = 99;
        $fsa[50]['LUI'] = 99; $fsa[51]['LUI'] = 99; $fsa[52]['LUI'] = 99; $fsa[53]['LUI'] = 99; $fsa[54]['LUI'] = 99;
        $fsa[50]['III'] = 99; $fsa[51]['III'] = 99; $fsa[52]['III'] = 99; $fsa[53]['III'] = 99; $fsa[54]['III'] = 99;
        $fsa[50]['NTE'] = 99; $fsa[51]['NTE'] = 99; $fsa[52]['NTE'] = 99; $fsa[53]['NTE'] = 99; $fsa[54]['NTE'] = 99;
        $fsa[50]['COM'] = 99; $fsa[51]['COM'] = 99; $fsa[52]['COM'] = 99; $fsa[53]['COM'] = 99; $fsa[54]['COM'] = 99;
        $fsa[50]['EMS'] = 99; $fsa[51]['EMS'] = 99; $fsa[52]['EMS'] = 99; $fsa[53]['EMS'] = 99; $fsa[54]['EMS'] = 99;
        $fsa[50]['QTY'] = 99; $fsa[51]['QTY'] = 99; $fsa[52]['QTY'] = 99; $fsa[53]['QTY'] = 99; $fsa[54]['QTY'] = 99;
        $fsa[50]['ATV'] = 99; $fsa[51]['ATV'] = 99; $fsa[52]['ATV'] = 99; $fsa[53]['ATV'] = 99; $fsa[54]['ATV'] = 99;
        $fsa[50]['AMT'] = 99; $fsa[51]['AMT'] = 99; $fsa[52]['AMT'] = 99; $fsa[53]['AMT'] = 99; $fsa[54]['AMT'] = 99;
        $fsa[50]['MSG'] = 99; $fsa[51]['MSG'] = 51; $fsa[52]['MSG'] = 99; $fsa[53]['MSG'] = 99; $fsa[54]['MSG'] = 99;
        $fsa[50]['SSE'] = 99; $fsa[51]['SSE'] = 99; $fsa[52]['SSE'] = 99; $fsa[53]['SSE'] = 99; $fsa[54]['SSE'] = 99;
        $fsa[50]['DEG'] = 99; $fsa[51]['DEG'] = 99; $fsa[52]['DEG'] = 99; $fsa[53]['DEG'] = 99; $fsa[54]['DEG'] = 99;
        $fsa[50]['FOS'] = 99; $fsa[51]['FOS'] = 99; $fsa[52]['FOS'] = 99; $fsa[53]['FOS'] = 99; $fsa[54]['FOS'] = 99;
        $fsa[50]['RSD'] = 29; $fsa[51]['RSD'] = 99; $fsa[52]['RSD'] = 99; $fsa[53]['RSD'] = 99; $fsa[54]['RSD'] = 99;
        $fsa[50]['RQS'] = 30; $fsa[51]['RQS'] = 30; $fsa[52]['RQS'] = 99; $fsa[53]['RQS'] = 99; $fsa[54]['RQS'] = 99;
        $fsa[50]['SST'] = 31; $fsa[51]['SST'] = 31; $fsa[52]['SST'] = 31; $fsa[53]['SST'] = 31; $fsa[54]['SST'] = 31;
        $fsa[50]['SUM'] = 99; $fsa[51]['SUM'] = 99; $fsa[52]['SUM'] = 56; $fsa[53]['SUM'] = 56; $fsa[54]['SUM'] = 56;
        $fsa[50]['SES'] = 99; $fsa[51]['SES'] = 99; $fsa[52]['SES'] = 57; $fsa[53]['SES'] = 57; $fsa[54]['SES'] = 57;
        $fsa[50]['CRS'] = 99; $fsa[51]['CRS'] = 99; $fsa[52]['CRS'] = 99; $fsa[53]['CRS'] = 99; $fsa[54]['CRS'] = 99;
        $fsa[50]['TST'] = 32; $fsa[51]['TST'] = 32; $fsa[52]['TST'] = 32; $fsa[53]['TST'] = 32; $fsa[54]['TST'] = 32;
        $fsa[50]['SBT'] = 99; $fsa[51]['SBT'] = 99; $fsa[52]['SBT'] = 99; $fsa[53]['SBT'] = 99; $fsa[54]['SBT'] = 99;
        $fsa[50]['SRE'] = 99; $fsa[51]['SRE'] = 99; $fsa[52]['SRE'] = 99; $fsa[53]['SRE'] = 99; $fsa[54]['SRE'] = 99;
        $fsa[50]['PCL'] = 33; $fsa[51]['PCL'] = 33; $fsa[52]['PCL'] = 33; $fsa[53]['PCL'] = 33; $fsa[54]['PCL'] = 33;
        $fsa[50]['LX']  = 34; $fsa[51]['LX']  = 34; $fsa[52]['LX']  = 34; $fsa[53]['LX']  = 34; $fsa[54]['LX']  = 34;
        $fsa[50]['LT']  = 35; $fsa[51]['LT']  = 35; $fsa[52]['LT']  = 35; $fsa[53]['LT']  = 35; $fsa[54]['LT']  = 35;
        $fsa[50]['LTE'] = 99; $fsa[51]['LTE'] = 99; $fsa[52]['LTE'] = 99; $fsa[53]['LTE'] = 99; $fsa[54]['LTE'] = 99;
        $fsa[50]['SE']  = 36; $fsa[51]['SE']  = 36; $fsa[52]['SE']  = 36; $fsa[53]['SE']  = 36; $fsa[54]['SE']  = 36;
        $fsa[50]['GE']  = 99; $fsa[51]['GE']  = 99; $fsa[52]['GE']  = 99; $fsa[53]['GE']  = 99; $fsa[54]['GE']  = 99;
        $fsa[50]['IEA'] = 99; $fsa[51]['IEA'] = 99; $fsa[52]['IEA'] = 99; $fsa[53]['IEA'] = 99; $fsa[54]['IEA'] = 99;

        $fsa[55]['ISA'] = 99; $fsa[56]['ISA'] = 99; $fsa[57]['ISA'] = 99; $fsa[58]['ISA'] = 99; $fsa[59]['ISA'] = 99;
        $fsa[55]['GS']  = 99; $fsa[56]['GS']  = 99; $fsa[57]['GS']  = 99; $fsa[58]['GS']  = 99; $fsa[59]['GS']  = 99;
        $fsa[55]['ST']  = 99; $fsa[56]['ST']  = 99; $fsa[57]['ST']  = 99; $fsa[58]['ST']  = 99; $fsa[59]['ST']  = 99;
        $fsa[55]['BGN'] = 99; $fsa[56]['BGN'] = 99; $fsa[57]['BGN'] = 99; $fsa[58]['BGN'] = 99; $fsa[59]['BGN'] = 99;
        $fsa[55]['N1']  = 99; $fsa[56]['N1']  = 99; $fsa[57]['N1']  = 99; $fsa[58]['N1']  = 99; $fsa[59]['N1']  = 99;
        $fsa[55]['N2']  = 99; $fsa[56]['N2']  = 99; $fsa[57]['N2']  = 99; $fsa[58]['N2']  = 99; $fsa[59]['N2']  = 99;
        $fsa[55]['N3']  = 99; $fsa[56]['N3']  = 99; $fsa[57]['N3']  = 99; $fsa[58]['N3']  = 99; $fsa[59]['N3']  = 99;
        $fsa[55]['N4']  = 99; $fsa[56]['N4']  = 99; $fsa[57]['N4']  = 99; $fsa[58]['N4']  = 99; $fsa[59]['N4']  = 60;
        $fsa[55]['PER'] = 99; $fsa[56]['PER'] = 99; $fsa[57]['PER'] = 99; $fsa[58]['PER'] = 99; $fsa[59]['PER'] = 99;
        $fsa[55]['REF'] = 99; $fsa[56]['REF'] = 99; $fsa[57]['REF'] = 99; $fsa[58]['REF'] = 99; $fsa[59]['REF'] = 99;
        $fsa[55]['DTP'] = 99; $fsa[56]['DTP'] = 99; $fsa[57]['DTP'] = 99; $fsa[58]['DTP'] = 99; $fsa[59]['DTP'] = 99;
        $fsa[55]['IN1'] = 99; $fsa[56]['IN1'] = 99; $fsa[57]['IN1'] = 99; $fsa[58]['IN1'] = 99; $fsa[59]['IN1'] = 99;
        $fsa[55]['IN2'] = 99; $fsa[56]['IN2'] = 99; $fsa[57]['IN2'] = 99; $fsa[58]['IN2'] = 99; $fsa[59]['IN2'] = 99;
        $fsa[55]['DMG'] = 99; $fsa[56]['DMG'] = 99; $fsa[57]['DMG'] = 99; $fsa[58]['DMG'] = 99; $fsa[59]['DMG'] = 99;
        $fsa[55]['IND'] = 99; $fsa[56]['IND'] = 99; $fsa[57]['IND'] = 99; $fsa[58]['IND'] = 99; $fsa[59]['IND'] = 99;
        $fsa[55]['IMM'] = 99; $fsa[56]['IMM'] = 99; $fsa[57]['IMM'] = 99; $fsa[58]['IMM'] = 99; $fsa[59]['IMM'] = 99;
        $fsa[55]['LUI'] = 99; $fsa[56]['LUI'] = 99; $fsa[57]['LUI'] = 99; $fsa[58]['LUI'] = 99; $fsa[59]['LUI'] = 99;
        $fsa[55]['III'] = 99; $fsa[56]['III'] = 99; $fsa[57]['III'] = 99; $fsa[58]['III'] = 99; $fsa[59]['III'] = 99;
        $fsa[55]['NTE'] = 99; $fsa[56]['NTE'] = 99; $fsa[57]['NTE'] = 99; $fsa[58]['NTE'] = 78; $fsa[59]['NTE'] = 99;
        $fsa[55]['COM'] = 99; $fsa[56]['COM'] = 99; $fsa[57]['COM'] = 99; $fsa[58]['COM'] = 99; $fsa[59]['COM'] = 99;
        $fsa[55]['EMS'] = 99; $fsa[56]['EMS'] = 99; $fsa[57]['EMS'] = 99; $fsa[58]['EMS'] = 99; $fsa[59]['EMS'] = 99;
        $fsa[55]['QTY'] = 99; $fsa[56]['QTY'] = 99; $fsa[57]['QTY'] = 99; $fsa[58]['QTY'] = 99; $fsa[59]['QTY'] = 99;
        $fsa[55]['ATV'] = 99; $fsa[56]['ATV'] = 99; $fsa[57]['ATV'] = 99; $fsa[58]['ATV'] = 99; $fsa[59]['ATV'] = 99;
        $fsa[55]['AMT'] = 99; $fsa[56]['AMT'] = 99; $fsa[57]['AMT'] = 99; $fsa[58]['AMT'] = 99; $fsa[59]['AMT'] = 99;
        $fsa[55]['MSG'] = 99; $fsa[56]['MSG'] = 99; $fsa[57]['MSG'] = 99; $fsa[58]['MSG'] = 99; $fsa[59]['MSG'] = 99;
        $fsa[55]['SSE'] = 99; $fsa[56]['SSE'] = 99; $fsa[57]['SSE'] = 99; $fsa[58]['SSE'] = 99; $fsa[59]['SSE'] = 61;
        $fsa[55]['DEG'] = 99; $fsa[56]['DEG'] = 99; $fsa[57]['DEG'] = 99; $fsa[58]['DEG'] = 99; $fsa[59]['DEG'] = 99;
        $fsa[55]['FOS'] = 99; $fsa[56]['FOS'] = 99; $fsa[57]['FOS'] = 99; $fsa[58]['FOS'] = 99; $fsa[59]['FOS'] = 99;
        $fsa[55]['RSD'] = 99; $fsa[56]['RSD'] = 99; $fsa[57]['RSD'] = 99; $fsa[58]['RSD'] = 99; $fsa[59]['RSD'] = 99;
        $fsa[55]['RQS'] = 99; $fsa[56]['RQS'] = 99; $fsa[57]['RQS'] = 99; $fsa[58]['RQS'] = 99; $fsa[59]['RQS'] = 99;
        $fsa[55]['SST'] = 31; $fsa[56]['SST'] = 31; $fsa[57]['SST'] = 99; $fsa[58]['SST'] = 99; $fsa[59]['SST'] = 99;
        $fsa[55]['SUM'] = 56; $fsa[56]['SUM'] = 56; $fsa[57]['SUM'] = 99; $fsa[58]['SUM'] = 99; $fsa[59]['SUM'] = 62;
        $fsa[55]['SES'] = 57; $fsa[56]['SES'] = 57; $fsa[57]['SES'] = 57; $fsa[58]['SES'] = 99; $fsa[59]['SES'] = 63;
        $fsa[55]['CRS'] = 99; $fsa[56]['CRS'] = 99; $fsa[57]['CRS'] = 76; $fsa[58]['CRS'] = 99; $fsa[59]['CRS'] = 99;
        $fsa[55]['TST'] = 32; $fsa[56]['TST'] = 32; $fsa[57]['TST'] = 32; $fsa[58]['TST'] = 32; $fsa[59]['TST'] = 99;
        $fsa[55]['SBT'] = 99; $fsa[56]['SBT'] = 99; $fsa[57]['SBT'] = 99; $fsa[58]['SBT'] = 58; $fsa[59]['SBT'] = 99;
        $fsa[55]['SRE'] = 99; $fsa[56]['SRE'] = 99; $fsa[57]['SRE'] = 99; $fsa[58]['SRE'] = 77; $fsa[59]['SRE'] = 99;
        $fsa[55]['PCL'] = 33; $fsa[56]['PCL'] = 33; $fsa[57]['PCL'] = 33; $fsa[58]['PCL'] = 33; $fsa[59]['PCL'] = 33;
        $fsa[55]['LX']  = 34; $fsa[56]['LX']  = 34; $fsa[57]['LX']  = 34; $fsa[58]['LX']  = 34; $fsa[59]['LX']  = 34;
        $fsa[55]['LT']  = 35; $fsa[56]['LT']  = 35; $fsa[57]['LT']  = 35; $fsa[58]['LT']  = 35; $fsa[59]['LT']  = 35;
        $fsa[55]['LTE'] = 99; $fsa[56]['LTE'] = 99; $fsa[57]['LTE'] = 99; $fsa[58]['LTE'] = 99; $fsa[59]['LTE'] = 99;
        $fsa[55]['SE']  = 36; $fsa[56]['SE']  = 36; $fsa[57]['SE']  = 36; $fsa[58]['SE']  = 36; $fsa[59]['SE']  = 36;
        $fsa[55]['GE']  = 99; $fsa[56]['GE']  = 99; $fsa[57]['GE']  = 99; $fsa[58]['GE']  = 99; $fsa[59]['GE']  = 99;
        $fsa[55]['IEA'] = 99; $fsa[56]['IEA'] = 99; $fsa[57]['IEA'] = 99; $fsa[58]['IEA'] = 99; $fsa[59]['IEA'] = 99;

        $fsa[60]['ISA'] = 99; $fsa[61]['ISA'] = 99; $fsa[62]['ISA'] = 99; $fsa[63]['ISA'] = 99; $fsa[64]['ISA'] = 99;
        $fsa[60]['GS']  = 99; $fsa[61]['GS']  = 99; $fsa[62]['GS']  = 99; $fsa[63]['GS']  = 99; $fsa[64]['GS']  = 99;
        $fsa[60]['ST']  = 99; $fsa[61]['ST']  = 99; $fsa[62]['ST']  = 99; $fsa[63]['ST']  = 99; $fsa[64]['ST']  = 99;
        $fsa[60]['BGN'] = 99; $fsa[61]['BGN'] = 99; $fsa[62]['BGN'] = 99; $fsa[63]['BGN'] = 99; $fsa[64]['BGN'] = 99;
        $fsa[60]['N1']  = 99; $fsa[61]['N1']  = 99; $fsa[62]['N1']  = 99; $fsa[63]['N1']  = 99; $fsa[64]['N1']  = 99;
        $fsa[60]['N2']  = 99; $fsa[61]['N2']  = 99; $fsa[62]['N2']  = 99; $fsa[63]['N2']  = 99; $fsa[64]['N2']  = 99;
        $fsa[60]['N3']  = 99; $fsa[61]['N3']  = 99; $fsa[62]['N3']  = 99; $fsa[63]['N3']  = 99; $fsa[64]['N3']  = 99;
        $fsa[60]['N4']  = 99; $fsa[61]['N4']  = 99; $fsa[62]['N4']  = 99; $fsa[63]['N4']  = 99; $fsa[64]['N4']  = 99;
        $fsa[60]['PER'] = 99; $fsa[61]['PER'] = 99; $fsa[62]['PER'] = 99; $fsa[63]['PER'] = 99; $fsa[64]['PER'] = 99;
        $fsa[60]['REF'] = 99; $fsa[61]['REF'] = 99; $fsa[62]['REF'] = 99; $fsa[63]['REF'] = 99; $fsa[64]['REF'] = 99;
        $fsa[60]['DTP'] = 99; $fsa[61]['DTP'] = 99; $fsa[62]['DTP'] = 99; $fsa[63]['DTP'] = 99; $fsa[64]['DTP'] = 99;
        $fsa[60]['IN1'] = 99; $fsa[61]['IN1'] = 99; $fsa[62]['IN1'] = 99; $fsa[63]['IN1'] = 99; $fsa[64]['IN1'] = 99;
        $fsa[60]['IN2'] = 99; $fsa[61]['IN2'] = 99; $fsa[62]['IN2'] = 99; $fsa[63]['IN2'] = 99; $fsa[64]['IN2'] = 99;
        $fsa[60]['DMG'] = 99; $fsa[61]['DMG'] = 99; $fsa[62]['DMG'] = 99; $fsa[63]['DMG'] = 99; $fsa[64]['DMG'] = 99;
        $fsa[60]['IND'] = 99; $fsa[61]['IND'] = 99; $fsa[62]['IND'] = 99; $fsa[63]['IND'] = 99; $fsa[64]['IND'] = 99;
        $fsa[60]['IMM'] = 99; $fsa[61]['IMM'] = 99; $fsa[62]['IMM'] = 99; $fsa[63]['IMM'] = 99; $fsa[64]['IMM'] = 99;
        $fsa[60]['LUI'] = 99; $fsa[61]['LUI'] = 99; $fsa[62]['LUI'] = 99; $fsa[63]['LUI'] = 99; $fsa[64]['LUI'] = 99;
        $fsa[60]['III'] = 99; $fsa[61]['III'] = 99; $fsa[62]['III'] = 99; $fsa[63]['III'] = 99; $fsa[64]['III'] = 99;
        $fsa[60]['NTE'] = 99; $fsa[61]['NTE'] = 99; $fsa[62]['NTE'] = 99; $fsa[63]['NTE'] = 99; $fsa[64]['NTE'] = 99;
        $fsa[60]['COM'] = 99; $fsa[61]['COM'] = 99; $fsa[62]['COM'] = 99; $fsa[63]['COM'] = 99; $fsa[64]['COM'] = 99;
        $fsa[60]['EMS'] = 99; $fsa[61]['EMS'] = 99; $fsa[62]['EMS'] = 99; $fsa[63]['EMS'] = 99; $fsa[64]['EMS'] = 99;
        $fsa[60]['QTY'] = 99; $fsa[61]['QTY'] = 99; $fsa[62]['QTY'] = 99; $fsa[63]['QTY'] = 99; $fsa[64]['QTY'] = 99;
        $fsa[60]['ATV'] = 99; $fsa[61]['ATV'] = 99; $fsa[62]['ATV'] = 99; $fsa[63]['ATV'] = 99; $fsa[64]['ATV'] = 99;
        $fsa[60]['AMT'] = 99; $fsa[61]['AMT'] = 99; $fsa[62]['AMT'] = 99; $fsa[63]['AMT'] = 99; $fsa[64]['AMT'] = 99;
        $fsa[60]['MSG'] = 99; $fsa[61]['MSG'] = 99; $fsa[62]['MSG'] = 99; $fsa[63]['MSG'] = 99; $fsa[64]['MSG'] = 64;
        $fsa[60]['SSE'] = 61; $fsa[61]['SSE'] = 99; $fsa[62]['SSE'] = 99; $fsa[63]['SSE'] = 99; $fsa[64]['SSE'] = 99;
        $fsa[60]['DEG'] = 99; $fsa[61]['DEG'] = 99; $fsa[62]['DEG'] = 99; $fsa[63]['DEG'] = 80; $fsa[64]['DEG'] = 99;
        $fsa[60]['FOS'] = 99; $fsa[61]['FOS'] = 99; $fsa[62]['FOS'] = 99; $fsa[63]['FOS'] = 99; $fsa[64]['FOS'] = 99;
        $fsa[60]['RSD'] = 99; $fsa[61]['RSD'] = 99; $fsa[62]['RSD'] = 99; $fsa[63]['RSD'] = 99; $fsa[64]['RSD'] = 99;
        $fsa[60]['RQS'] = 99; $fsa[61]['RQS'] = 99; $fsa[62]['RQS'] = 99; $fsa[63]['RQS'] = 99; $fsa[64]['RQS'] = 99;
        $fsa[60]['SST'] = 99; $fsa[61]['SST'] = 99; $fsa[62]['SST'] = 99; $fsa[63]['SST'] = 99; $fsa[64]['SST'] = 99;
        $fsa[60]['SUM'] = 62; $fsa[61]['SUM'] = 62; $fsa[62]['SUM'] = 99; $fsa[63]['SUM'] = 99; $fsa[64]['SUM'] = 99;
        $fsa[60]['SES'] = 63; $fsa[61]['SES'] = 63; $fsa[62]['SES'] = 63; $fsa[63]['SES'] = 63; $fsa[64]['SES'] = 99;
        $fsa[60]['CRS'] = 99; $fsa[61]['CRS'] = 99; $fsa[62]['CRS'] = 99; $fsa[63]['CRS'] = 79; $fsa[64]['CRS'] = 99;
        $fsa[60]['TST'] = 99; $fsa[61]['TST'] = 99; $fsa[62]['TST'] = 99; $fsa[63]['TST'] = 99; $fsa[64]['TST'] = 99;
        $fsa[60]['SBT'] = 99; $fsa[61]['SBT'] = 99; $fsa[62]['SBT'] = 99; $fsa[63]['SBT'] = 99; $fsa[64]['SBT'] = 99;
        $fsa[60]['SRE'] = 99; $fsa[61]['SRE'] = 99; $fsa[62]['SRE'] = 99; $fsa[63]['SRE'] = 99; $fsa[64]['SRE'] = 99;
        $fsa[60]['PCL'] = 33; $fsa[61]['PCL'] = 33; $fsa[62]['PCL'] = 33; $fsa[63]['PCL'] = 33; $fsa[64]['PCL'] = 99;
        $fsa[60]['LX']  = 34; $fsa[61]['LX']  = 34; $fsa[62]['LX']  = 34; $fsa[63]['LX']  = 34; $fsa[64]['LX']  = 34;
        $fsa[60]['LT']  = 35; $fsa[61]['LT']  = 35; $fsa[62]['LT']  = 35; $fsa[63]['LT']  = 35; $fsa[64]['LT']  = 35;
        $fsa[60]['LTE'] = 99; $fsa[61]['LTE'] = 99; $fsa[62]['LTE'] = 99; $fsa[63]['LTE'] = 99; $fsa[64]['LTE'] = 99;
        $fsa[60]['SE']  = 36; $fsa[61]['SE']  = 36; $fsa[62]['SE']  = 36; $fsa[63]['SE']  = 36; $fsa[64]['SE']  = 36;
        $fsa[60]['GE']  = 99; $fsa[61]['GE']  = 99; $fsa[62]['GE']  = 99; $fsa[63]['GE']  = 99; $fsa[64]['GE']  = 99;
        $fsa[60]['IEA'] = 99; $fsa[61]['IEA'] = 99; $fsa[62]['IEA'] = 99; $fsa[63]['IEA'] = 99; $fsa[64]['IEA'] = 99;

        $fsa[65]['ISA'] = 99; $fsa[66]['ISA'] = 99; $fsa[67]['ISA'] = 99; $fsa[68]['ISA'] = 99; $fsa[69]['ISA'] = 99;
        $fsa[65]['GS']  = 99; $fsa[66]['GS']  = 99; $fsa[67]['GS']  = 99; $fsa[68]['GS']  = 99; $fsa[69]['GS']  = 99;
        $fsa[65]['ST']  = 99; $fsa[66]['ST']  = 99; $fsa[67]['ST']  = 99; $fsa[68]['ST']  = 99; $fsa[69]['ST']  = 99;
        $fsa[65]['BGN'] = 99; $fsa[66]['BGN'] = 99; $fsa[67]['BGN'] = 99; $fsa[68]['BGN'] = 99; $fsa[69]['BGN'] = 99;
        $fsa[65]['N1']  = 68; $fsa[66]['N1']  = 68; $fsa[67]['N1']  = 68; $fsa[68]['N1']  = 99; $fsa[69]['N1']  = 99;
        $fsa[65]['N2']  = 99; $fsa[66]['N2']  = 99; $fsa[67]['N2']  = 99; $fsa[68]['N2']  = 99; $fsa[69]['N2']  = 99;
        $fsa[65]['N3']  = 69; $fsa[66]['N3']  = 69; $fsa[67]['N3']  = 69; $fsa[68]['N3']  = 69; $fsa[69]['N3']  = 99;
        $fsa[65]['N4']  = 70; $fsa[66]['N4']  = 70; $fsa[67]['N4']  = 70; $fsa[68]['N4']  = 70; $fsa[69]['N4']  = 70;
        $fsa[65]['PER'] = 99; $fsa[66]['PER'] = 99; $fsa[67]['PER'] = 99; $fsa[68]['PER'] = 99; $fsa[69]['PER'] = 99;
        $fsa[65]['REF'] = 99; $fsa[66]['REF'] = 99; $fsa[67]['REF'] = 99; $fsa[68]['REF'] = 99; $fsa[69]['REF'] = 99;
        $fsa[65]['DTP'] = 99; $fsa[66]['DTP'] = 99; $fsa[67]['DTP'] = 99; $fsa[68]['DTP'] = 99; $fsa[69]['DTP'] = 99;
        $fsa[65]['IN1'] = 99; $fsa[66]['IN1'] = 99; $fsa[67]['IN1'] = 99; $fsa[68]['IN1'] = 99; $fsa[69]['IN1'] = 99;
        $fsa[65]['IN2'] = 99; $fsa[66]['IN2'] = 99; $fsa[67]['IN2'] = 99; $fsa[68]['IN2'] = 99; $fsa[69]['IN2'] = 99;
        $fsa[65]['DMG'] = 99; $fsa[66]['DMG'] = 99; $fsa[67]['DMG'] = 99; $fsa[68]['DMG'] = 99; $fsa[69]['DMG'] = 99;
        $fsa[65]['IND'] = 99; $fsa[66]['IND'] = 99; $fsa[67]['IND'] = 99; $fsa[68]['IND'] = 99; $fsa[69]['IND'] = 99;
        $fsa[65]['IMM'] = 99; $fsa[66]['IMM'] = 99; $fsa[67]['IMM'] = 99; $fsa[68]['IMM'] = 99; $fsa[69]['IMM'] = 99;
        $fsa[65]['LUI'] = 99; $fsa[66]['LUI'] = 99; $fsa[67]['LUI'] = 99; $fsa[68]['LUI'] = 99; $fsa[69]['LUI'] = 99;
        $fsa[65]['III'] = 99; $fsa[66]['III'] = 99; $fsa[67]['III'] = 99; $fsa[68]['III'] = 99; $fsa[69]['III'] = 99;
        $fsa[65]['NTE'] = 99; $fsa[66]['NTE'] = 99; $fsa[67]['NTE'] = 99; $fsa[68]['NTE'] = 99; $fsa[69]['NTE'] = 99;
        $fsa[65]['COM'] = 67; $fsa[66]['COM'] = 67; $fsa[67]['COM'] = 67; $fsa[68]['COM'] = 99; $fsa[69]['COM'] = 99;
        $fsa[65]['EMS'] = 99; $fsa[66]['EMS'] = 99; $fsa[67]['EMS'] = 99; $fsa[68]['EMS'] = 99; $fsa[69]['EMS'] = 99;
        $fsa[65]['QTY'] = 66; $fsa[66]['QTY'] = 99; $fsa[67]['QTY'] = 99; $fsa[68]['QTY'] = 99; $fsa[69]['QTY'] = 99;
        $fsa[65]['ATV'] = 99; $fsa[66]['ATV'] = 99; $fsa[67]['ATV'] = 99; $fsa[68]['ATV'] = 99; $fsa[69]['ATV'] = 99;
        $fsa[65]['AMT'] = 99; $fsa[66]['AMT'] = 99; $fsa[67]['AMT'] = 99; $fsa[68]['AMT'] = 99; $fsa[69]['AMT'] = 99;
        $fsa[65]['MSG'] = 72; $fsa[66]['MSG'] = 72; $fsa[67]['MSG'] = 72; $fsa[68]['MSG'] = 72; $fsa[69]['MSG'] = 72;
        $fsa[65]['SSE'] = 99; $fsa[66]['SSE'] = 99; $fsa[67]['SSE'] = 99; $fsa[68]['SSE'] = 99; $fsa[69]['SSE'] = 99;
        $fsa[65]['DEG'] = 99; $fsa[66]['DEG'] = 99; $fsa[67]['DEG'] = 99; $fsa[68]['DEG'] = 99; $fsa[69]['DEG'] = 99;
        $fsa[65]['FOS'] = 99; $fsa[66]['FOS'] = 99; $fsa[67]['FOS'] = 99; $fsa[68]['FOS'] = 99; $fsa[69]['FOS'] = 99;
        $fsa[65]['RSD'] = 99; $fsa[66]['RSD'] = 99; $fsa[67]['RSD'] = 99; $fsa[68]['RSD'] = 99; $fsa[69]['RSD'] = 99;
        $fsa[65]['RQS'] = 99; $fsa[66]['RQS'] = 99; $fsa[67]['RQS'] = 99; $fsa[68]['RQS'] = 99; $fsa[69]['RQS'] = 99;
        $fsa[65]['SST'] = 99; $fsa[66]['SST'] = 99; $fsa[67]['SST'] = 99; $fsa[68]['SST'] = 99; $fsa[69]['SST'] = 99;
        $fsa[65]['SUM'] = 99; $fsa[66]['SUM'] = 99; $fsa[67]['SUM'] = 99; $fsa[68]['SUM'] = 99; $fsa[69]['SUM'] = 99;
        $fsa[65]['SES'] = 99; $fsa[66]['SES'] = 99; $fsa[67]['SES'] = 99; $fsa[68]['SES'] = 99; $fsa[69]['SES'] = 99;
        $fsa[65]['CRS'] = 99; $fsa[66]['CRS'] = 99; $fsa[67]['CRS'] = 99; $fsa[68]['CRS'] = 99; $fsa[69]['CRS'] = 99;
        $fsa[65]['TST'] = 99; $fsa[66]['TST'] = 99; $fsa[67]['TST'] = 99; $fsa[68]['TST'] = 99; $fsa[69]['TST'] = 99;
        $fsa[65]['SBT'] = 99; $fsa[66]['SBT'] = 99; $fsa[67]['SBT'] = 99; $fsa[68]['SBT'] = 99; $fsa[69]['SBT'] = 99;
        $fsa[65]['SRE'] = 99; $fsa[66]['SRE'] = 99; $fsa[67]['SRE'] = 99; $fsa[68]['SRE'] = 99; $fsa[69]['SRE'] = 99;
        $fsa[65]['PCL'] = 99; $fsa[66]['PCL'] = 99; $fsa[67]['PCL'] = 99; $fsa[68]['PCL'] = 99; $fsa[69]['PCL'] = 99;
        $fsa[65]['LX']  = 99; $fsa[66]['LX']  = 99; $fsa[67]['LX']  = 99; $fsa[68]['LX']  = 99; $fsa[69]['LX']  = 99;
        $fsa[65]['LT']  = 35; $fsa[66]['LT']  = 35; $fsa[67]['LT']  = 35; $fsa[68]['LT']  = 35; $fsa[69]['LT']  = 35;
        $fsa[65]['LTE'] = 71; $fsa[66]['LTE'] = 71; $fsa[67]['LTE'] = 71; $fsa[68]['LTE'] = 71; $fsa[69]['LTE'] = 71;
        $fsa[65]['SE']  = 36; $fsa[66]['SE']  = 36; $fsa[67]['SE']  = 36; $fsa[68]['SE']  = 36; $fsa[69]['SE']  = 36;
        $fsa[65]['GE']  = 99; $fsa[66]['GE']  = 99; $fsa[67]['GE']  = 99; $fsa[68]['GE']  = 99; $fsa[69]['GE']  = 99;
        $fsa[65]['IEA'] = 99; $fsa[66]['IEA'] = 99; $fsa[67]['IEA'] = 99; $fsa[68]['IEA'] = 99; $fsa[69]['IEA'] = 99;

        $fsa[70]['ISA'] = 99; $fsa[71]['ISA'] = 99; $fsa[72]['ISA'] = 99; $fsa[73]['ISA'] = 99; $fsa[74]['ISA'] = 99;
        $fsa[70]['GS']  = 99; $fsa[71]['GS']  = 99; $fsa[72]['GS']  = 99; $fsa[73]['GS']  = 2;  $fsa[74]['GS']  = 99;
        $fsa[70]['ST']  = 99; $fsa[71]['ST']  = 99; $fsa[72]['ST']  = 99; $fsa[73]['ST']  = 99; $fsa[74]['ST']  = 99;
        $fsa[70]['BGN'] = 99; $fsa[71]['BGN'] = 99; $fsa[72]['BGN'] = 99; $fsa[73]['BGN'] = 99; $fsa[74]['BGN'] = 99;
        $fsa[70]['N1']  = 99; $fsa[71]['N1']  = 99; $fsa[72]['N1']  = 99; $fsa[73]['N1']  = 99; $fsa[74]['N1']  = 25;
        $fsa[70]['N2']  = 99; $fsa[71]['N2']  = 99; $fsa[72]['N2']  = 99; $fsa[73]['N2']  = 99; $fsa[74]['N2']  = 99;
        $fsa[70]['N3']  = 99; $fsa[71]['N3']  = 99; $fsa[72]['N3']  = 99; $fsa[73]['N3']  = 99; $fsa[74]['N3']  = 99;
        $fsa[70]['N4']  = 99; $fsa[71]['N4']  = 99; $fsa[72]['N4']  = 99; $fsa[73]['N4']  = 99; $fsa[74]['N4']  = 99;
        $fsa[70]['PER'] = 99; $fsa[71]['PER'] = 99; $fsa[72]['PER'] = 99; $fsa[73]['PER'] = 99; $fsa[74]['PER'] = 99;
        $fsa[70]['REF'] = 99; $fsa[71]['REF'] = 99; $fsa[72]['REF'] = 99; $fsa[73]['REF'] = 99; $fsa[74]['REF'] = 99;
        $fsa[70]['DTP'] = 99; $fsa[71]['DTP'] = 99; $fsa[72]['DTP'] = 99; $fsa[73]['DTP'] = 99; $fsa[74]['DTP'] = 74;
        $fsa[70]['IN1'] = 99; $fsa[71]['IN1'] = 99; $fsa[72]['IN1'] = 99; $fsa[73]['IN1'] = 99; $fsa[74]['IN1'] = 14;
        $fsa[70]['IN2'] = 99; $fsa[71]['IN2'] = 99; $fsa[72]['IN2'] = 99; $fsa[73]['IN2'] = 99; $fsa[74]['IN2'] = 99;
        $fsa[70]['DMG'] = 99; $fsa[71]['DMG'] = 99; $fsa[72]['DMG'] = 99; $fsa[73]['DMG'] = 99; $fsa[74]['DMG'] = 99;
        $fsa[70]['IND'] = 99; $fsa[71]['IND'] = 99; $fsa[72]['IND'] = 99; $fsa[73]['IND'] = 99; $fsa[74]['IND'] = 99;
        $fsa[70]['IMM'] = 99; $fsa[71]['IMM'] = 99; $fsa[72]['IMM'] = 99; $fsa[73]['IMM'] = 99; $fsa[74]['IMM'] = 99;
        $fsa[70]['LUI'] = 99; $fsa[71]['LUI'] = 99; $fsa[72]['LUI'] = 99; $fsa[73]['LUI'] = 99; $fsa[74]['LUI'] = 99;
        $fsa[70]['III'] = 99; $fsa[71]['III'] = 99; $fsa[72]['III'] = 99; $fsa[73]['III'] = 99; $fsa[74]['III'] = 99;
        $fsa[70]['NTE'] = 99; $fsa[71]['NTE'] = 99; $fsa[72]['NTE'] = 99; $fsa[73]['NTE'] = 99; $fsa[74]['NTE'] = 99;
        $fsa[70]['COM'] = 99; $fsa[71]['COM'] = 99; $fsa[72]['COM'] = 99; $fsa[73]['COM'] = 99; $fsa[74]['COM'] = 99;
        $fsa[70]['EMS'] = 99; $fsa[71]['EMS'] = 99; $fsa[72]['EMS'] = 99; $fsa[73]['EMS'] = 99; $fsa[74]['EMS'] = 42;
        $fsa[70]['QTY'] = 99; $fsa[71]['QTY'] = 99; $fsa[72]['QTY'] = 99; $fsa[73]['QTY'] = 99; $fsa[74]['QTY'] = 75;
        $fsa[70]['ATV'] = 99; $fsa[71]['ATV'] = 99; $fsa[72]['ATV'] = 99; $fsa[73]['ATV'] = 99; $fsa[74]['ATV'] = 26;
        $fsa[70]['AMT'] = 99; $fsa[71]['AMT'] = 99; $fsa[72]['AMT'] = 99; $fsa[73]['AMT'] = 99; $fsa[74]['AMT'] = 27;
        $fsa[70]['MSG'] = 72; $fsa[71]['MSG'] = 72; $fsa[72]['MSG'] = 99; $fsa[73]['MSG'] = 99; $fsa[74]['MSG'] = 99;
        $fsa[70]['SSE'] = 99; $fsa[71]['SSE'] = 99; $fsa[72]['SSE'] = 99; $fsa[73]['SSE'] = 99; $fsa[74]['SSE'] = 28;
        $fsa[70]['DEG'] = 99; $fsa[71]['DEG'] = 99; $fsa[72]['DEG'] = 99; $fsa[73]['DEG'] = 99; $fsa[74]['DEG'] = 99;
        $fsa[70]['FOS'] = 99; $fsa[71]['FOS'] = 99; $fsa[72]['FOS'] = 99; $fsa[73]['FOS'] = 99; $fsa[74]['FOS'] = 99;
        $fsa[70]['RSD'] = 99; $fsa[71]['RSD'] = 99; $fsa[72]['RSD'] = 99; $fsa[73]['RSD'] = 99; $fsa[74]['RSD'] = 29;
        $fsa[70]['RQS'] = 99; $fsa[71]['RQS'] = 99; $fsa[72]['RQS'] = 99; $fsa[73]['RQS'] = 99; $fsa[74]['RQS'] = 30;
        $fsa[70]['SST'] = 99; $fsa[71]['SST'] = 99; $fsa[72]['SST'] = 99; $fsa[73]['SST'] = 99; $fsa[74]['SST'] = 31;
        $fsa[70]['SUM'] = 99; $fsa[71]['SUM'] = 99; $fsa[72]['SUM'] = 99; $fsa[73]['SUM'] = 99; $fsa[74]['SUM'] = 99;
        $fsa[70]['SES'] = 99; $fsa[71]['SES'] = 99; $fsa[72]['SES'] = 99; $fsa[73]['SES'] = 99; $fsa[74]['SES'] = 99;
        $fsa[70]['CRS'] = 99; $fsa[71]['CRS'] = 99; $fsa[72]['CRS'] = 99; $fsa[73]['CRS'] = 99; $fsa[74]['CRS'] = 99;
        $fsa[70]['TST'] = 99; $fsa[71]['TST'] = 99; $fsa[72]['TST'] = 99; $fsa[73]['TST'] = 99; $fsa[74]['TST'] = 32;
        $fsa[70]['SBT'] = 99; $fsa[71]['SBT'] = 99; $fsa[72]['SBT'] = 99; $fsa[73]['SBT'] = 99; $fsa[74]['SBT'] = 99;
        $fsa[70]['SRE'] = 99; $fsa[71]['SRE'] = 99; $fsa[72]['SRE'] = 99; $fsa[73]['SRE'] = 99; $fsa[74]['SRE'] = 99;
        $fsa[70]['PCL'] = 99; $fsa[71]['PCL'] = 99; $fsa[72]['PCL'] = 99; $fsa[73]['PCL'] = 99; $fsa[74]['PCL'] = 33;
        $fsa[70]['LX']  = 99; $fsa[71]['LX']  = 99; $fsa[72]['LX']  = 99; $fsa[73]['LX']  = 99; $fsa[74]['LX']  = 34;
        $fsa[70]['LT']  = 35; $fsa[71]['LT']  = 35; $fsa[72]['LT']  = 35; $fsa[73]['LT']  = 99; $fsa[74]['LT']  = 35;
        $fsa[70]['LTE'] = 71; $fsa[71]['LTE'] = 71; $fsa[72]['LTE'] = 99; $fsa[73]['LTE'] = 99; $fsa[74]['LTE'] = 99;
        $fsa[70]['SE']  = 36; $fsa[71]['SE']  = 36; $fsa[72]['SE']  = 36; $fsa[73]['SE']  = 99; $fsa[74]['SE']  = 36;
        $fsa[70]['GE']  = 99; $fsa[71]['GE']  = 99; $fsa[72]['GE']  = 99; $fsa[73]['GE']  = 99; $fsa[74]['GE']  = 99;
        $fsa[70]['IEA'] = 99; $fsa[71]['IEA'] = 99; $fsa[72]['IEA'] = 99; $fsa[73]['IEA'] = 0;  $fsa[74]['IEA'] = 99;

        $fsa[75]['ISA'] = 99; $fsa[76]['ISA'] = 99; $fsa[77]['ISA'] = 99; $fsa[78]['ISA'] = 99; $fsa[79]['ISA'] = 99;
        $fsa[75]['GS']  = 99; $fsa[76]['GS']  = 99; $fsa[77]['GS']  = 99; $fsa[78]['GS']  = 99; $fsa[79]['GS']  = 99;
        $fsa[75]['ST']  = 99; $fsa[76]['ST']  = 99; $fsa[77]['ST']  = 99; $fsa[78]['ST']  = 99; $fsa[79]['ST']  = 99;
        $fsa[75]['BGN'] = 99; $fsa[76]['BGN'] = 99; $fsa[77]['BGN'] = 99; $fsa[78]['BGN'] = 99; $fsa[79]['BGN'] = 99;
        $fsa[75]['N1']  = 25; $fsa[76]['N1']  = 99; $fsa[77]['N1']  = 99; $fsa[78]['N1']  = 99; $fsa[79]['N1']  = 99;
        $fsa[75]['N2']  = 99; $fsa[76]['N2']  = 99; $fsa[77]['N2']  = 99; $fsa[78]['N2']  = 99; $fsa[79]['N2']  = 99;
        $fsa[75]['N3']  = 99; $fsa[76]['N3']  = 99; $fsa[77]['N3']  = 99; $fsa[78]['N3']  = 99; $fsa[79]['N3']  = 99;
        $fsa[75]['N4']  = 99; $fsa[76]['N4']  = 99; $fsa[77]['N4']  = 99; $fsa[78]['N4']  = 99; $fsa[79]['N4']  = 99;
        $fsa[75]['PER'] = 99; $fsa[76]['PER'] = 99; $fsa[77]['PER'] = 99; $fsa[78]['PER'] = 99; $fsa[79]['PER'] = 99;
        $fsa[75]['REF'] = 99; $fsa[76]['REF'] = 99; $fsa[77]['REF'] = 99; $fsa[78]['REF'] = 99; $fsa[79]['REF'] = 99;
        $fsa[75]['DTP'] = 99; $fsa[76]['DTP'] = 99; $fsa[77]['DTP'] = 99; $fsa[78]['DTP'] = 99; $fsa[79]['DTP'] = 99;
        $fsa[75]['IN1'] = 14; $fsa[76]['IN1'] = 99; $fsa[77]['IN1'] = 99; $fsa[78]['IN1'] = 99; $fsa[79]['IN1'] = 99;
        $fsa[75]['IN2'] = 99; $fsa[76]['IN2'] = 99; $fsa[77]['IN2'] = 99; $fsa[78]['IN2'] = 99; $fsa[79]['IN2'] = 99;
        $fsa[75]['DMG'] = 99; $fsa[76]['DMG'] = 99; $fsa[77]['DMG'] = 99; $fsa[78]['DMG'] = 99; $fsa[79]['DMG'] = 99;
        $fsa[75]['IND'] = 99; $fsa[76]['IND'] = 99; $fsa[77]['IND'] = 99; $fsa[78]['IND'] = 99; $fsa[79]['IND'] = 99;
        $fsa[75]['IMM'] = 99; $fsa[76]['IMM'] = 99; $fsa[77]['IMM'] = 99; $fsa[78]['IMM'] = 99; $fsa[79]['IMM'] = 99;
        $fsa[75]['LUI'] = 99; $fsa[76]['LUI'] = 99; $fsa[77]['LUI'] = 99; $fsa[78]['LUI'] = 99; $fsa[79]['LUI'] = 99;
        $fsa[75]['III'] = 99; $fsa[76]['III'] = 99; $fsa[77]['III'] = 99; $fsa[78]['III'] = 99; $fsa[79]['III'] = 99;
        $fsa[75]['NTE'] = 99; $fsa[76]['NTE'] = 81; $fsa[77]['NTE'] = 82; $fsa[78]['NTE'] = 78; $fsa[79]['NTE'] = 83;
        $fsa[75]['COM'] = 99; $fsa[76]['COM'] = 99; $fsa[77]['COM'] = 99; $fsa[78]['COM'] = 99; $fsa[79]['COM'] = 99;
        $fsa[75]['EMS'] = 42; $fsa[76]['EMS'] = 99; $fsa[77]['EMS'] = 99; $fsa[78]['EMS'] = 99; $fsa[79]['EMS'] = 99;
        $fsa[75]['QTY'] = 99; $fsa[76]['QTY'] = 99; $fsa[77]['QTY'] = 99; $fsa[78]['QTY'] = 99; $fsa[79]['QTY'] = 99;
        $fsa[75]['ATV'] = 26; $fsa[76]['ATV'] = 99; $fsa[77]['ATV'] = 99; $fsa[78]['ATV'] = 99; $fsa[79]['ATV'] = 99;
        $fsa[75]['AMT'] = 27; $fsa[76]['AMT'] = 99; $fsa[77]['AMT'] = 99; $fsa[78]['AMT'] = 99; $fsa[79]['AMT'] = 99;
        $fsa[75]['MSG'] = 99; $fsa[76]['MSG'] = 99; $fsa[77]['MSG'] = 99; $fsa[78]['MSG'] = 99; $fsa[79]['MSG'] = 99;
        $fsa[75]['SSE'] = 28; $fsa[76]['SSE'] = 99; $fsa[77]['SSE'] = 99; $fsa[78]['SSE'] = 99; $fsa[79]['SSE'] = 99;
        $fsa[75]['DEG'] = 99; $fsa[76]['DEG'] = 99; $fsa[77]['DEG'] = 99; $fsa[78]['DEG'] = 99; $fsa[79]['DEG'] = 80;
        $fsa[75]['FOS'] = 99; $fsa[76]['FOS'] = 99; $fsa[77]['FOS'] = 99; $fsa[78]['FOS'] = 99; $fsa[79]['FOS'] = 99;
        $fsa[75]['RSD'] = 29; $fsa[76]['RSD'] = 99; $fsa[77]['RSD'] = 99; $fsa[78]['RSD'] = 99; $fsa[79]['RSD'] = 99;
        $fsa[75]['RQS'] = 30; $fsa[76]['RQS'] = 99; $fsa[77]['RQS'] = 99; $fsa[78]['RQS'] = 99; $fsa[79]['RQS'] = 99;
        $fsa[75]['SST'] = 31; $fsa[76]['SST'] = 31; $fsa[77]['SST'] = 99; $fsa[78]['SST'] = 99; $fsa[79]['SST'] = 99;
        $fsa[75]['SUM'] = 99; $fsa[76]['SUM'] = 99; $fsa[77]['SUM'] = 99; $fsa[78]['SUM'] = 99; $fsa[79]['SUM'] = 99;
        $fsa[75]['SES'] = 99; $fsa[76]['SES'] = 63; $fsa[77]['SES'] = 99; $fsa[78]['SES'] = 99; $fsa[79]['SES'] = 63;
        $fsa[75]['CRS'] = 99; $fsa[76]['CRS'] = 76; $fsa[77]['CRS'] = 99; $fsa[78]['CRS'] = 99; $fsa[79]['CRS'] = 79;
        $fsa[75]['TST'] = 32; $fsa[76]['TST'] = 32; $fsa[77]['TST'] = 32; $fsa[78]['TST'] = 32; $fsa[79]['TST'] = 99;
        $fsa[75]['SBT'] = 99; $fsa[76]['SBT'] = 99; $fsa[77]['SBT'] = 58; $fsa[78]['SBT'] = 58; $fsa[79]['SBT'] = 99;
        $fsa[75]['SRE'] = 99; $fsa[76]['SRE'] = 99; $fsa[77]['SRE'] = 77; $fsa[78]['SRE'] = 99; $fsa[79]['SRE'] = 99;
        $fsa[75]['PCL'] = 33; $fsa[76]['PCL'] = 33; $fsa[77]['PCL'] = 33; $fsa[78]['PCL'] = 33; $fsa[79]['PCL'] = 33;
        $fsa[75]['LX']  = 34; $fsa[76]['LX']  = 34; $fsa[77]['LX']  = 34; $fsa[78]['LX']  = 34; $fsa[79]['LX']  = 34;
        $fsa[75]['LT']  = 35; $fsa[76]['LT']  = 35; $fsa[77]['LT']  = 35; $fsa[78]['LT']  = 35; $fsa[79]['LT']  = 35;
        $fsa[75]['LTE'] = 99; $fsa[76]['LTE'] = 99; $fsa[77]['LTE'] = 99; $fsa[78]['LTE'] = 99; $fsa[79]['LTE'] = 99;
        $fsa[75]['SE']  = 36; $fsa[76]['SE']  = 36; $fsa[77]['SE']  = 36; $fsa[78]['SE']  = 36; $fsa[79]['SE']  = 36;
        $fsa[75]['GE']  = 99; $fsa[76]['GE']  = 99; $fsa[77]['GE']  = 99; $fsa[78]['GE']  = 99; $fsa[79]['GE']  = 99;
        $fsa[75]['IEA'] = 99; $fsa[76]['IEA'] = 99; $fsa[77]['IEA'] = 99; $fsa[78]['IEA'] = 99; $fsa[79]['IEA'] = 99;

        $fsa[80]['ISA'] = 99; $fsa[81]['ISA'] = 99; $fsa[82]['ISA'] = 99; $fsa[83]['ISA'] = 99; $fsa[84]['ISA'] = 99;
        $fsa[80]['GS']  = 99; $fsa[81]['GS']  = 99; $fsa[82]['GS']  = 99; $fsa[83]['GS']  = 99; $fsa[84]['GS']  = 99;
        $fsa[80]['ST']  = 99; $fsa[81]['ST']  = 99; $fsa[82]['ST']  = 99; $fsa[83]['ST']  = 99; $fsa[84]['ST']  = 99;
        $fsa[80]['BGN'] = 99; $fsa[81]['BGN'] = 99; $fsa[82]['BGN'] = 99; $fsa[83]['BGN'] = 99; $fsa[84]['BGN'] = 99;
        $fsa[80]['N1']  = 99; $fsa[81]['N1']  = 99; $fsa[82]['N1']  = 99; $fsa[83]['N1']  = 99; $fsa[84]['N1']  = 99;
        $fsa[80]['N2']  = 99; $fsa[81]['N2']  = 99; $fsa[82]['N2']  = 99; $fsa[83]['N2']  = 99; $fsa[84]['N2']  = 99;
        $fsa[80]['N3']  = 99; $fsa[81]['N3']  = 99; $fsa[82]['N3']  = 99; $fsa[83]['N3']  = 99; $fsa[84]['N3']  = 99;
        $fsa[80]['N4']  = 99; $fsa[81]['N4']  = 99; $fsa[82]['N4']  = 99; $fsa[83]['N4']  = 99; $fsa[84]['N4']  = 99;
        $fsa[80]['PER'] = 99; $fsa[81]['PER'] = 99; $fsa[82]['PER'] = 99; $fsa[83]['PER'] = 99; $fsa[84]['PER'] = 99;
        $fsa[80]['REF'] = 99; $fsa[81]['REF'] = 99; $fsa[82]['REF'] = 99; $fsa[83]['REF'] = 99; $fsa[84]['REF'] = 99;
        $fsa[80]['DTP'] = 99; $fsa[81]['DTP'] = 99; $fsa[82]['DTP'] = 99; $fsa[83]['DTP'] = 99; $fsa[84]['DTP'] = 99;
        $fsa[80]['IN1'] = 99; $fsa[81]['IN1'] = 99; $fsa[82]['IN1'] = 99; $fsa[83]['IN1'] = 99; $fsa[84]['IN1'] = 99;
        $fsa[80]['IN2'] = 99; $fsa[81]['IN2'] = 99; $fsa[82]['IN2'] = 99; $fsa[83]['IN2'] = 99; $fsa[84]['IN2'] = 99;
        $fsa[80]['DMG'] = 99; $fsa[81]['DMG'] = 99; $fsa[82]['DMG'] = 99; $fsa[83]['DMG'] = 99; $fsa[84]['DMG'] = 99;
        $fsa[80]['IND'] = 99; $fsa[81]['IND'] = 99; $fsa[82]['IND'] = 99; $fsa[83]['IND'] = 99; $fsa[84]['IND'] = 99;
        $fsa[80]['IMM'] = 99; $fsa[81]['IMM'] = 99; $fsa[82]['IMM'] = 99; $fsa[83]['IMM'] = 99; $fsa[84]['IMM'] = 99;
        $fsa[80]['LUI'] = 99; $fsa[81]['LUI'] = 99; $fsa[82]['LUI'] = 99; $fsa[83]['LUI'] = 99; $fsa[84]['LUI'] = 99;
        $fsa[80]['III'] = 99; $fsa[81]['III'] = 99; $fsa[82]['III'] = 99; $fsa[83]['III'] = 99; $fsa[84]['III'] = 99;
        $fsa[80]['NTE'] = 86; $fsa[81]['NTE'] = 99; $fsa[82]['NTE'] = 82; $fsa[83]['NTE'] = 99; $fsa[84]['NTE'] = 86;
        $fsa[80]['COM'] = 99; $fsa[81]['COM'] = 99; $fsa[82]['COM'] = 99; $fsa[83]['COM'] = 99; $fsa[84]['COM'] = 99;
        $fsa[80]['EMS'] = 99; $fsa[81]['EMS'] = 99; $fsa[82]['EMS'] = 99; $fsa[83]['EMS'] = 99; $fsa[84]['EMS'] = 99;
        $fsa[80]['QTY'] = 99; $fsa[81]['QTY'] = 99; $fsa[82]['QTY'] = 99; $fsa[83]['QTY'] = 99; $fsa[84]['QTY'] = 99;
        $fsa[80]['ATV'] = 99; $fsa[81]['ATV'] = 99; $fsa[82]['ATV'] = 99; $fsa[83]['ATV'] = 99; $fsa[84]['ATV'] = 99;
        $fsa[80]['AMT'] = 99; $fsa[81]['AMT'] = 99; $fsa[82]['AMT'] = 99; $fsa[83]['AMT'] = 99; $fsa[84]['AMT'] = 99;
        $fsa[80]['MSG'] = 99; $fsa[81]['MSG'] = 99; $fsa[82]['MSG'] = 99; $fsa[83]['MSG'] = 99; $fsa[84]['MSG'] = 99;
        $fsa[80]['SSE'] = 99; $fsa[81]['SSE'] = 99; $fsa[82]['SSE'] = 99; $fsa[83]['SSE'] = 99; $fsa[84]['SSE'] = 99;
        $fsa[80]['DEG'] = 80; $fsa[81]['DEG'] = 99; $fsa[82]['DEG'] = 99; $fsa[83]['DEG'] = 99; $fsa[84]['DEG'] = 80;
        $fsa[80]['FOS'] = 85; $fsa[81]['FOS'] = 99; $fsa[82]['FOS'] = 99; $fsa[83]['FOS'] = 99; $fsa[84]['FOS'] = 85;
        $fsa[80]['RSD'] = 99; $fsa[81]['RSD'] = 99; $fsa[82]['RSD'] = 99; $fsa[83]['RSD'] = 99; $fsa[84]['RSD'] = 99;
        $fsa[80]['RQS'] = 99; $fsa[81]['RQS'] = 99; $fsa[82]['RQS'] = 99; $fsa[83]['RQS'] = 99; $fsa[84]['RQS'] = 99;
        $fsa[80]['SST'] = 99; $fsa[81]['SST'] = 31; $fsa[82]['SST'] = 99; $fsa[83]['SST'] = 99; $fsa[84]['SST'] = 99;
        $fsa[80]['SUM'] = 84; $fsa[81]['SUM'] = 99; $fsa[82]['SUM'] = 99; $fsa[83]['SUM'] = 99; $fsa[84]['SUM'] = 99;
        $fsa[80]['SES'] = 63; $fsa[81]['SES'] = 57; $fsa[82]['SES'] = 99; $fsa[83]['SES'] = 63; $fsa[84]['SES'] = 63;
        $fsa[80]['CRS'] = 99; $fsa[81]['CRS'] = 76; $fsa[82]['CRS'] = 99; $fsa[83]['CRS'] = 79; $fsa[84]['CRS'] = 99;
        $fsa[80]['TST'] = 99; $fsa[81]['TST'] = 32; $fsa[82]['TST'] = 32; $fsa[83]['TST'] = 99; $fsa[84]['TST'] = 99;
        $fsa[80]['SBT'] = 99; $fsa[81]['SBT'] = 99; $fsa[82]['SBT'] = 58; $fsa[83]['SBT'] = 99; $fsa[84]['SBT'] = 99;
        $fsa[80]['SRE'] = 99; $fsa[81]['SRE'] = 99; $fsa[82]['SRE'] = 99; $fsa[83]['SRE'] = 99; $fsa[84]['SRE'] = 99;
        $fsa[80]['PCL'] = 33; $fsa[81]['PCL'] = 33; $fsa[82]['PCL'] = 33; $fsa[83]['PCL'] = 33; $fsa[84]['PCL'] = 33;
        $fsa[80]['LX']  = 34; $fsa[81]['LX']  = 34; $fsa[82]['LX']  = 34; $fsa[83]['LX']  = 34; $fsa[84]['LX']  = 34;
        $fsa[80]['LT']  = 35; $fsa[81]['LT']  = 35; $fsa[82]['LT']  = 35; $fsa[83]['LT']  = 35; $fsa[84]['LT']  = 35;
        $fsa[80]['LTE'] = 99; $fsa[81]['LTE'] = 99; $fsa[82]['LTE'] = 99; $fsa[83]['LTE'] = 99; $fsa[84]['LTE'] = 99;
        $fsa[80]['SE']  = 36; $fsa[81]['SE']  = 36; $fsa[82]['SE']  = 36; $fsa[83]['SE']  = 36; $fsa[84]['SE']  = 36;
        $fsa[80]['GE']  = 99; $fsa[81]['GE']  = 99; $fsa[82]['GE']  = 99; $fsa[83]['GE']  = 99; $fsa[84]['GE']  = 99;
        $fsa[80]['IEA'] = 99; $fsa[81]['IEA'] = 99; $fsa[82]['IEA'] = 99; $fsa[83]['IEA'] = 99; $fsa[84]['IEA'] = 99;

        $fsa[85]['ISA'] = 99; $fsa[86]['ISA'] = 99;
        $fsa[85]['GS']  = 99; $fsa[86]['GS']  = 99;
        $fsa[85]['ST']  = 99; $fsa[86]['ST']  = 99;
        $fsa[85]['BGN'] = 99; $fsa[86]['BGN'] = 99;
        $fsa[85]['N1']  = 99; $fsa[86]['N1']  = 99;
        $fsa[85]['N2']  = 99; $fsa[86]['N2']  = 99;
        $fsa[85]['N3']  = 99; $fsa[86]['N3']  = 99;
        $fsa[85]['N4']  = 99; $fsa[86]['N4']  = 99;
        $fsa[85]['PER'] = 99; $fsa[86]['PER'] = 99;
        $fsa[85]['REF'] = 99; $fsa[86]['REF'] = 99;
        $fsa[85]['DTP'] = 99; $fsa[86]['DTP'] = 99;
        $fsa[85]['IN1'] = 99; $fsa[86]['IN1'] = 99;
        $fsa[85]['IN2'] = 99; $fsa[86]['IN2'] = 99;
        $fsa[85]['DMG'] = 99; $fsa[86]['DMG'] = 99;
        $fsa[85]['IND'] = 99; $fsa[86]['IND'] = 99;
        $fsa[85]['IMM'] = 99; $fsa[86]['IMM'] = 99;
        $fsa[85]['LUI'] = 99; $fsa[86]['LUI'] = 99;
        $fsa[85]['III'] = 99; $fsa[86]['III'] = 99;
        $fsa[85]['NTE'] = 86; $fsa[86]['NTE'] = 86;
        $fsa[85]['COM'] = 99; $fsa[86]['COM'] = 99;
        $fsa[85]['EMS'] = 99; $fsa[86]['EMS'] = 99;
        $fsa[85]['QTY'] = 99; $fsa[86]['QTY'] = 99;
        $fsa[85]['ATV'] = 99; $fsa[86]['ATV'] = 99;
        $fsa[85]['AMT'] = 99; $fsa[86]['AMT'] = 99;
        $fsa[85]['MSG'] = 99; $fsa[86]['MSG'] = 99;
        $fsa[85]['SSE'] = 99; $fsa[86]['SSE'] = 99;
        $fsa[85]['DEG'] = 80; $fsa[86]['DEG'] = 80;
        $fsa[85]['FOS'] = 85; $fsa[86]['FOS'] = 99;
        $fsa[85]['RSD'] = 99; $fsa[86]['RSD'] = 99;
        $fsa[85]['RQS'] = 99; $fsa[86]['RQS'] = 99;
        $fsa[85]['SST'] = 99; $fsa[86]['SST'] = 99;
        $fsa[85]['SUM'] = 99; $fsa[86]['SUM'] = 99;
        $fsa[85]['SES'] = 63; $fsa[86]['SES'] = 63;
        $fsa[85]['CRS'] = 99; $fsa[86]['CRS'] = 99;
        $fsa[85]['TST'] = 99; $fsa[86]['TST'] = 99;
        $fsa[85]['SBT'] = 99; $fsa[86]['SBT'] = 99;
        $fsa[85]['SRE'] = 99; $fsa[86]['SRE'] = 99;
        $fsa[85]['PCL'] = 33; $fsa[86]['PCL'] = 33;
        $fsa[85]['LX']  = 34; $fsa[86]['LX']  = 34;
        $fsa[85]['LT']  = 35; $fsa[86]['LT']  = 35;
        $fsa[85]['LTE'] = 99; $fsa[86]['LTE'] = 99;
        $fsa[85]['SE']  = 36; $fsa[86]['SE']  = 36;
        $fsa[85]['GE']  = 99; $fsa[86]['GE']  = 99;
        $fsa[85]['IEA'] = 99; $fsa[86]['IEA'] = 99;

        return $fsa;
    }

    private static function getTokens()
    {
        return array(
            'ISA',
            'GS',
            'ST',
            'BGN',
            'N1',
            'N2',
            'N3',
            'N4',
            'PER',
            'REF',
            'DTP',
            'IN1',
            'IN2',
            'DMG',
            'IND',
            'IMM',
            'LUI',
            'III',
            'NTE',
            'COM',
            'EMS',
            'QTY',
            'ATV',
            'AMT',
            'MSG',
            'SSE',
            'DEG',
            'FOS',
            'RSD',
            'RQS',
            'SST',
            'SUM',
            'SES',
            'CRS',
            'TST',
            'SBT',
            'SRE',
            'PCL',
            'LX',
            'LT',
            'LTE',
            'SE',
            'GE',
            'IEA',
        );
    }
}

