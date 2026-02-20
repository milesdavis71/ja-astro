<?php
// Test handler.php directly without HTTP request
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing handler.php directly\n";
echo "============================\n\n";

// Set up the environment exactly as the handler expects
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'register_student';

// Create test data
$testData = [
    'school' => 'Budapesti Gimnázium',
    'email' => 'test' . time() . '@example.com',
    'password' => 'testpassword123',
    's1_n' => 'Test Teacher 1',
    's1_e' => 'teacher1@example.com',
    's2_n' => 'Test Teacher 2',
    's2_e' => 'teacher2@example.com',
    's3_n' => 'Test Teacher 3',
    's3_e' => 'teacher3@example.com'
];

// Set the input data
$jsonData = json_encode($testData);
$tempFile = tmpfile();
fwrite($tempFile, $jsonData);
rewind($tempFile);

// Override php://input
stream_wrapper_register("test", "TestStream");
class TestStream
{
    private $position = 0;
    private $data;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        global $jsonData;
        $this->data = $jsonData;
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof()
    {
        return $this->position >= strlen($this->data);
    }

    public function stream_stat()
    {
        return [];
    }
}

// Temporarily override php://input
ini_set('allow_url_fopen', 1);
stream_wrapper_unregister('php');
stream_wrapper_register('php', 'TestStream');

echo "Test data:\n";
print_r($testData);
echo "\n";

echo "JSON data being sent:\n";
echo $jsonData . "\n\n";

// Capture output
ob_start();
try {
    include 'handler.php';
    $output = ob_get_clean();

    echo "Handler output:\n";
    echo $output . "\n";

    // Parse the JSON response
    $response = json_decode($output, true);
    if ($response) {
        if ($response['success']) {
            echo "✅ Registration successful!\n";
            if (isset($response['email_sent'])) {
                echo "Email sent: " . ($response['email_sent'] ? 'Yes' : 'No') . "\n";
                if (isset($response['email_message'])) {
                    echo "Email message: " . $response['email_message'] . "\n";
                }
            }
        } else {
            echo "❌ Registration failed: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid JSON response from handler\n";
        echo "Raw output: " . $output . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Restore original stream wrapper
stream_wrapper_restore('php');

echo "\n\nTest complete!\n";
