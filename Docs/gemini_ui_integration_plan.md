# Gemini UI Integration Plan

## 1. Objective

The goal of this plan is to integrate a user interface for managing the Gemini API key and provider selection within the Modesy admin panel. This will allow administrators to seamlessly switch between OpenAI and Gemini as the AI content provider and manage their respective API keys.

## 2. Analysis of the Existing UI

The current UI for the AI Writer settings is located in `app/Views/admin/settings/preferences.php`. It consists of a simple form with two fields:

-   **Status**: A radio button to enable or disable the AI Writer feature.
-   **API Key**: A text input field for the OpenAI API key.

The form submits to the `Admin/aiWriterPost` route, which is handled by a controller that saves the settings.

## 3. Proposed UI Changes

To support both OpenAI and Gemini, the following changes will be made to the UI:

1.  **Provider Selection**: A dropdown menu or radio button group will be added to allow the administrator to select the AI provider (OpenAI or Gemini).
2.  **Conditional API Key Fields**: The API key input field will be dynamically displayed based on the selected provider. When "OpenAI" is selected, the OpenAI API key field will be shown, and when "Gemini" is selected, the Gemini API key field will be shown.
3.  **Updated Labels**: The section title will be updated to "AI Content Creator (OpenAI & Gemini)" to reflect the dual-provider support.

## 4. Implementation Steps

The implementation will be divided into the following steps:

### Step 1: Update the View File

The `app/Views/admin/settings/preferences.php` file will be modified to include the new UI elements.

-   Add a dropdown menu for provider selection.
-   Add a new input field for the Gemini API key.
-   Use JavaScript to show/hide the API key fields based on the selected provider.

### Step 2: Update the Controller

The controller responsible for handling the `Admin/aiWriterPost` route will be updated to save the new settings.

-   Retrieve the selected AI provider from the form submission.
-   Retrieve the Gemini API key from the form submission.
-   Save the provider and API key to the database.

### Step 3: Update the Database

A new column will be added to the `general_settings` table to store the selected AI provider and the Gemini API key.

-   `ai_provider` (VARCHAR): To store the selected AI provider ('openai' or 'gemini').
-   `gemini_api_key` (TEXT): To store the encrypted Gemini API key.

## 5. Conclusion

By following this plan, we will create a user-friendly interface for managing both OpenAI and Gemini settings within the Modesy admin panel. This will provide administrators with greater flexibility and control over the AI-powered content generation features of the platform.