<?php
/**
 * JSON Data Processor Script
 * 
 * This script provides various functionalities to manipulate and analyze JSON data
 * submitted through a web form. It allows users to remove data, list main keys, show data types,
 * and generate a JSON schema that describes the structure of the JSON data.
 * 
 * @developer Thee Soontornsing, Toni Maxx
 * @website http://logicbaker.com
 */

// Initialize variables to retain form data and results
$outputJson = "";
$originalJson = "";
$actionType = ""; // To retain the selected action type
$errorMsg = "";  // To store error messages

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $originalJson = $_POST['jsonData'] ?? ''; // Get the JSON data from the form
    $actionType = $_POST['actionType'] ?? 'removeData'; // Default to 'removeData'
    $jsonData = json_decode($originalJson, true);

    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMsg = "Invalid JSON input: " . json_last_error_msg();
        $jsonData = null;  // Reset jsonData to prevent further processing
    }

    if ($jsonData !== null) {
        switch ($actionType) {
            case 'listKeys':
                $keyList = array_keys($jsonData);
                $outputJson = json_encode($keyList, JSON_PRETTY_PRINT);
                break;

            case 'showDataType':
                $outputJson = json_encode(array_map_recursive($jsonData, function($value) {
                    return gettype($value);
                }), JSON_PRETTY_PRINT);
                break;

            case 'showJSONSchema':
                $outputJson = json_encode(generateJSONSchema($jsonData), JSON_PRETTY_PRINT);
                break;

            case 'removeData':
            default:
                $outputJson = json_encode(array_map_recursive($jsonData, function($value) {
                    return null;
                }), JSON_PRETTY_PRINT);
                break;
        }
    }
}

// Helper function for recursive array mapping
function array_map_recursive($array, $callback) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = array_map_recursive($value, $callback);
        } else {
            $array[$key] = call_user_func($callback, $value);
        }
    }
    return $array;
}

// Function to generate JSON schema from a given array (JSON structure)
function generateJSONSchema($data, $isRoot = true) {
    $schema = $isRoot ? ['type' => 'object', 'properties' => []] : [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            // Determine if array is associative (object) or sequential (array)
            if (array_keys($value) !== range(0, count($value) - 1)) {
                $schema['properties'][$key] = generateJSONSchema($value, false);
            } else {
                $schema['properties'][$key] = ['type' => 'array', 'items' => generateJSONSchema($value[0], false)];
            }
        } else {
            $schema['properties'][$key] = ['type' => strtolower(gettype($value))];
        }
    }
    return $schema;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Data Processor</title>
    <!-- Styles and Script for UI Functionality -->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form {
            max-width: 800px;
            width: 100%;
            position: relative;
        }
        textarea {
            width: 100%;
            height: 150px;
            resize: none;
        }
        select, input, textarea, button {
            margin: 10px 0;
        }
        #loadJson {
            position: absolute;
            right: 10px;
            top: 5px;
        }
        .help {
            max-width: 800px;
            text-align: left;
            font-size: 0.95em;
            margin-top: 20px;
        }
    </style>
    <script>
        function loadSampleJson() {
            const sampleJson = {
                "name": "John Doe",
                "age": 30,
                "isActive": true,
                "address": {
                    "street": "123 Main St",
                    "city": "Anytown",
                    "state": "CA",
                    "zip": "12345"
                },
                "phoneNumbers": [
                    "123-456-7890",
                    "555-555-5555"
                ],
                "avatar": "https://example.com/avatar.jpg"
            };
            document.getElementById('jsonData').value = JSON.stringify(sampleJson, null, 4);
        }
    </script>
</head>
<body>
    <h1>JSON Data Processor</h1>
    <form method="post" action="removejsondata.php">
        <button type="button" id="loadJson" onclick="loadSampleJson()">Load Sample JSON</button>
        <textarea id="jsonData" name="jsonData" rows="10" cols="30" placeholder="Enter JSON here"><?php echo htmlspecialchars($originalJson); ?></textarea><br>
        <label for="actionType">Action Type:</label>
        <select name="actionType" id="actionType">
            <option value="removeData" <?php echo $actionType === 'removeData' ? 'selected' : ''; ?>>Remove Data</option>
            <option value="listKeys" <?php echo $actionType === 'listKeys' ? 'selected' : ''; ?>>List Main Keys Only</option>
            <option value="showDataType" <?php echo $actionType === 'showDataType' ? 'selected' : ''; ?>>Show Data Type</option>
            <option value="showJSONSchema" <?php echo $actionType === 'showJSONSchema' ? 'selected' : ''; ?>>Show JSON Schema</option>
        </select><br>
        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($errorMsg)): ?>
        <h2>Error:</h2>
        <p style="color: red;"><?php echo htmlspecialchars($errorMsg); ?></p>
    <?php endif; ?>

    <?php if (!empty($outputJson) && empty($errorMsg)): ?>
        <h2>Processed Output:</h2>
        <pre><?php echo htmlspecialchars($outputJson); ?></pre>
    <?php endif; ?>

    <div class="help">
        <h3>How to Use:</h3>
        <p>1. Paste your JSON into the textarea above, or click 'Load Sample JSON' to fill with sample data.</p>
        <p>2. Select the desired action from the dropdown menu.</p>
        <p>3. Click "Submit" to process your JSON.</p>
        <ul>
            <li><strong>Remove Data:</strong> Strips all values, leaving JSON keys intact.</li>
            <li><strong>List Main Keys Only:</strong> Lists the top-level keys of the JSON object.</li>
            <li><strong>Show Data Type:</strong> Displays the data type of each value in the JSON object.</li>
            <li><strong>Show JSON Schema:</strong> Generates a JSON schema that describes the structure of the JSON data.</li>
        </ul>
    </div>
</body>
</html>
