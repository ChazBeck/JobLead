# Zapier Integration Setup Guide

## Overview

The JobLead application integrates with Zapier to automatically analyze job descriptions using OpenAI. When a job is uploaded, it's sent to Zapier, which uses AI to detect which ESG offerings are mentioned in the job description.

## ESG Offerings Detected

The AI analyzes job descriptions for these 8 offerings:
1. Sustainability Reporting & Disclosure
2. Data Management & ESG Metrics
3. ESG Strategy & Roadmapping
4. Regulatory Compliance & Standards
5. ESG Ratings & Rankings
6. Stakeholder Engagement & Communication
7. Governance & Policy Development
8. Technology & Tools for Sustainability

## Zapier Zap Setup

### Step 1: Create the Trigger (Webhook)

1. In Zapier, create a new Zap
2. Choose **Webhooks by Zapier** as the trigger
3. Select **Catch Hook**
4. Copy the webhook URL provided (looks like: `https://hooks.zapier.com/hooks/catch/12345/abcdef/`)
5. Click **Continue**
6. **Test the trigger** - we'll send test data in Step 4

### Step 2: Add OpenAI Action

1. Click **+** to add an action
2. Search for and select **OpenAI (ChatGPT, Whisper, DALL-E)**
3. Choose **Conversation** as the action event
4. Connect your OpenAI account
5. Configure the conversation:

**Model:** `gpt-4` or `gpt-3.5-turbo`

**Prompt:**
```
Analyze the following job description and determine if it mentions any of these ESG consulting offerings. Respond with ONLY a JSON object containing true/false for each offering, plus a "notes" field with brief confirmation text for any detected offerings.

Job Description:
Company: {{company}}
Role: {{role_title}}
Description: {{job_description}}

Required JSON format:
{
  "sustainability_reporting": true/false,
  "data_management_esg": true/false,
  "esg_strategy_roadmapping": true/false,
  "regulatory_compliance": true/false,
  "esg_ratings_rankings": true/false,
  "stakeholder_engagement": true/false,
  "governance_policy": true/false,
  "technology_tools": true/false,
  "notes": "Brief explanation of detected offerings"
}
```

**Map these fields from Step 1:**
- `{{company}}` → company
- `{{role_title}}` → role_title
- `{{job_description}}` → job_description

6. Click **Continue** and test

### Step 3: Parse the AI Response

1. Click **+** to add another action
2. Search for **Code by Zapier**
3. Choose **Run Python** as the action event
4. Configure:

**Input Data:**
- `ai_response`: Map the response from Step 2 (OpenAI)
- `job_id`: Map job_id from Step 1

**Code:**
```python
import json

# Parse the AI response
ai_response = input_data.get('ai_response', '{}')

# Try to extract JSON from the response
try:
    # Sometimes OpenAI wraps JSON in markdown code blocks
    if '```json' in ai_response:
        start = ai_response.find('```json') + 7
        end = ai_response.find('```', start)
        ai_response = ai_response[start:end].strip()
    elif '```' in ai_response:
        start = ai_response.find('```') + 3
        end = ai_response.find('```', start)
        ai_response = ai_response[start:end].strip()
    
    data = json.loads(ai_response)
except:
    # If parsing fails, return default values
    data = {
        'sustainability_reporting': False,
        'data_management_esg': False,
        'esg_strategy_roadmapping': False,
        'regulatory_compliance': False,
        'esg_ratings_rankings': False,
        'stakeholder_engagement': False,
        'governance_policy': False,
        'technology_tools': False,
        'notes': 'Failed to parse AI response'
    }

# Add job_id to the output
data['job_id'] = int(input_data.get('job_id'))

output = {'result': json.dumps(data)}
```

5. Click **Continue** and test

### Step 4: Send Results Back to JobLead

1. Click **+** to add final action
2. Search for **Webhooks by Zapier**
3. Choose **POST** as the action event
4. Configure:

**URL:** `https://tools.veerl.es/apps/joblead/public/?page=webhook_receive`

**Payload Type:** json

**Data:**
- Map the `result` field from Step 3 directly as raw JSON

**Headers:**
- Content-Type: `application/json`

5. Click **Continue** and test

### Step 5: Turn On Your Zap

1. Name your Zap: "JobLead - AI ESG Analysis"
2. Turn it **ON**
3. Copy the webhook URL from Step 1

## Application Configuration

### Development (Local)

Edit `/config/config.php` and update:

```php
define('WEBHOOKS', [
    'zapier_ai_analysis' => 'https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID'
]);
```

### Production

1. In cPanel File Manager, edit `/config/config.prod.php`
2. Update the WEBHOOKS array:

```php
define('WEBHOOKS', [
    'zapier_ai_analysis' => 'https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID'
]);
```

## Testing the Integration

1. Upload a job via the Upload page
2. Check Zapier's Task History to see if the webhook was received
3. Verify OpenAI processed the job description
4. Check the dashboard - the job should show "✓ X offerings" once processed
5. View job details to see which offerings were detected

## Troubleshooting

### Webhook not received by Zapier
- Check that WEBHOOKS constant is set correctly
- Verify the webhook URL is from the correct Zap
- Check PHP error logs for cURL errors

### AI analysis not returning to JobLead
- Verify the callback URL in Step 4 is correct
- Check that webhook_receive.php is accessible
- Look at Zapier Task History for error messages
- Check PHP error logs on your server

### Offerings not showing in dashboard
- Run the database migration: `database/migrations/001_add_esg_offerings.sql`
- Verify columns were added: `DESCRIBE jobs;`
- Check that ai_analyzed_at timestamp is being set

## JSON Format Examples

### Sent TO Zapier (Step 1):
```json
{
  "job_id": 123,
  "company": "Acme Corp",
  "role_title": "Director of Sustainability",
  "job_description": "Lead our ESG reporting and compliance initiatives...",
  "callback_url": "https://tools.veerl.es/apps/joblead/public/?page=webhook_receive"
}
```

### Sent FROM Zapier (Step 4):
```json
{
  "job_id": 123,
  "offerings": {
    "sustainability_reporting": true,
    "data_management_esg": false,
    "esg_strategy_roadmapping": true,
    "regulatory_compliance": true,
    "esg_ratings_rankings": false,
    "stakeholder_engagement": false,
    "governance_policy": false,
    "technology_tools": false
  },
  "notes": "Job mentions sustainability reporting, ESG strategy development, and regulatory compliance requirements."
}
```

## Cost Considerations

- **Zapier:** Tasks are consumed per job uploaded
- **OpenAI:** Each job analysis uses API credits (~$0.01-0.03 per job with GPT-3.5-turbo)
- Consider upgrading Zapier plan if uploading many jobs

## Security Notes

- Webhook URLs contain sensitive tokens - never commit to Git
- The webhook_receive endpoint validates incoming data
- Consider adding API key authentication for production use
