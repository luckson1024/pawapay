# Gemini Integration Guide for Modesy AI Writer

## 1. Introduction

This guide provides a step-by-step walkthrough for integrating Google's Gemini models into the Modesy AI Writer, enabling a dual-provider system alongside the existing OpenAI integration. By following these instructions, you will be able to seamlessly switch between OpenAI and Gemini models, leverage the unique capabilities of each, and enhance the platform's AI-powered content generation features.

The integration is designed to be modular and non-invasive, ensuring that the existing functionality remains intact while extending the system's capabilities. All configurations will be managed through the `AIWriter.php` config file, providing a centralized and straightforward way to manage the AI settings.

## 2. Prerequisites

Before you begin, ensure you have the following:

-   **Gemini API Key**: You will need a valid API key from Google AI Studio to authenticate requests to the Gemini API.
-   **Modesy Installation**: This guide assumes you have a working installation of Modesy with the AI Writer feature enabled.
-   **PHP and cURL**: Ensure that your server has PHP installed with the cURL extension enabled for making HTTP requests.

## 3. Integration Steps

The integration process is divided into several steps, each focusing on a specific part of the system. Follow these steps carefully to ensure a successful integration.

### Step 1: Update the `AIWriter.php` Configuration

The first step is to update the `app/Config/AIWriter.php` file to include the necessary configurations for Gemini. This involves adding the Gemini API URL, the new Gemini models, and a mechanism to select the AI provider.

1.  **Add Gemini API URL**: Add a new class variable for the Gemini API URL.
2.  **Add Gemini Models**: Extend the `$models` array to include the available Gemini models.
3.  **Add Provider Selection**: Introduce a new configuration option to select the default AI provider (OpenAI or Gemini).

Here is how the top of your `app/Config/AIWriter.php` should look after the changes:

```php
<?php

namespace Config;

/**
 * @immutable
 */
class AIWriter
{
    // AI Provider: 'openai' or 'gemini'
    public static $aiProvider = 'openai';

    // API URLs
    public static $openaiApiUrl = 'https://api.openai.com/v1/chat/completions';
    public static $geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    // AI Models
    public static $models = [
        // OpenAI Models
        'gpt-4o' => 'GPT-4 Omni',
        'gpt-4o-mini' => 'GPT-4o Mini',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        // Gemini Models
        'gemini-1.5-pro' => 'Gemini 1.5 Pro',
        'gemini-1.5-flash' => 'Gemini 1.5 Flash',
    ];

    //AI Form Defaults
    public static $formDefaults = [
        'model' => 'gpt-4o-mini',
        'tone' => 'casual', //academic, casual, critical, formal, humorous, inspirational, persuasive, professional
        'length' => 'medium', //very_short, short, medium, long, very_long
    ];

    //AI Promt
    public static $basePrompt = "Write a {content_type} about the topic: {topic}. It should be {length} in length. Use a {tone} tone. Write it in {language}. Only return the plain text without any introductions, explanations, or formatting.";
}
```

### Step 2: Modify the `generateText` and `generateAIPrompt` Functions

The `generateText` function in `app/Config/AIWriter.php` needs to be updated to handle both OpenAI and Gemini models. This will involve adding a conditional check for the selected AI provider and calling the appropriate function to generate the text. The `generateAIPrompt` function is also updated for better compatibility.

1.  **Add Provider Check**: At the beginning of the `generateText` function, add a check for the `$aiProvider` variable.
2.  **Create Gemini-Specific Function**: Create a new private function, `generateTextGemini`, to handle the API request to the Gemini API.
3.  **Update Existing Logic**: The existing cURL logic for OpenAI can be moved into a new private function, `generateTextOpenAI`, for better organization.
4.  **Update `generateAIPrompt`**: Replace the `match` statement with a `switch` statement for broader PHP version compatibility.

```php
// app/Config/AIWriter.php

class AIWriter
{
    // ... (previous configurations)

    //generate AI promt
    public static function generateAIPrompt($options)
    {
        switch ($options->contentType) {
            case 'product':
                $contentType = 'product description';
                break;
            case 'page':
                $contentType = 'page description';
                break;
            case 'blog':
                $contentType = 'blog article';
                break;
            default:
                $contentType = 'text';
                break;
        }

        $prompt = self::$basePrompt;
        if (!empty($prompt)) {
            $prompt = str_replace('{length}', $options->length, $prompt);
            $prompt = str_replace('{content_type}', $contentType, $prompt);
            $prompt = str_replace('{topic}', $options->topic, $prompt);
            $prompt = str_replace('{tone}', $options->tone, $prompt);
            $prompt = str_replace('{language}', $options->langName, $prompt);
        }
        return $prompt;
    }

    // generate text
    public static function generateText($options)
    {
        if (self::$aiProvider === 'gemini') {
            return self::generateTextGemini($options);
        }
        return self::generateTextOpenAI($options);
    }

    // generate text with OpenAI
    private static function generateTextOpenAI($options)
    {
        // ... (existing OpenAI cURL logic will go here)
    }

    // generate text with Gemini
    private static function generateTextGemini($options)
    {
        // ... (Gemini cURL logic to be added)
    }
}
```

### Step 3: Implement the `generateTextGemini` Function

Now, you will implement the `generateTextGemini` function to handle the API request to the Gemini API. This will involve constructing the request payload, setting the necessary headers, and processing the response.

1.  **Get API Key**: Retrieve the Gemini API key from the settings.
2.  **Build Prompt**: Generate the prompt using the existing `generateAIPrompt` function.
3.  **Construct Payload**: Create the JSON payload for the Gemini API request.
4.  **Make cURL Request**: Use cURL to send the request to the Gemini API and retrieve the response.
5.  **Process Response**: Parse the JSON response and extract the generated text.

Here is the full implementation for the `generateTextGemini` function:

```php
// app/Config/AIWriter.php

private static function generateTextGemini($options)
{
    $aiWriter = aiWriter();
    if (empty($aiWriter->apiKey)) {
        return ['status' => 'error', 'message' => 'API key is missing. Add your API key from the Preferences section.'];
    }

    $prompt = self::generateAIPrompt($options);
    $model = (!empty(self::$models) && array_key_exists($options->model, self::$models)) ? $options->model : 'gemini-1.5-flash';

    $data = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, self::$geminiApiUrl . '?key=' . $aiWriter->apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        return ['status' => 'error', 'message' => 'cURL error: ' . $errorMessage];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['status' => 'error', 'message' => 'Unexpected response code: ' . $httpCode];
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $responseData['candidates'][0]['content']['parts'][0]['text'];
        return ['status' => 'success', 'content' => nl2br($content)];
    }
    
    if (isset($responseData['error']['message'])) {
        return ['status' => 'error', 'message' => $responseData['error']['message']];
    }

    return ['status' => 'error', 'message' => 'No valid response content found.'];
}
```

## 4. Conclusion

By following this guide, you have successfully integrated Gemini into the Modesy AI Writer. You can now switch between OpenAI and Gemini models by changing the `$aiProvider` variable in the `AIWriter.php` configuration file. This dual-provider setup provides greater flexibility and allows you to leverage the strengths of both AI models for your content generation needs.