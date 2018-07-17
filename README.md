# shedi
X12 Parser 130/189

## Split an EDI file into individual types (130, 131, 189, 997)
 
    // /path/to/EDIIN_1231.dat will be split into:
    // /path/for/output/edi/
    //   130.txt
    //   131.txt
    //   189.txt
    //   997.txt
    //   other.txt
    use Shedi\Util;
    try {
        Util::splitFile('/path/to/EDIIN_1213.dat', '/path/for/output/');
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

## Split Transcript file into JSON files

All efforts are made to generate unique file names.

    // /path/to/edi/130.txt will be split into:
    // /path/for/output/*.json
    use Shedi\Transcript;
    try {
        Transcript::splitFile('/path/to/edi/130.txt', '/path/for/output/');
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

## Split Application file into JSON files

Each filename will be the application ID from ApplyTexas.

    // path/to/edi/189.txt will be split into:
    // /path/for/output/*.json
    use Shedi\Application;
    try {
        Application::splitFile('/path/to/edi/189.txt', '/path/for/output/');
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

More docs and tests coming shortly...

