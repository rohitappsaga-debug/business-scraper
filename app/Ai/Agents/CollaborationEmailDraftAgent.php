<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider([Lab::Gemini, Lab::OpenAI])]
#[Model(Lab::Gemini, 'gemini-1.5-flash')]
#[Model(Lab::OpenAI, 'gpt-4o-mini')]
class CollaborationEmailDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are an AI assistant specialized in writing professional business collaboration outreach emails. You will receive details about a business and should generate a tailored subject line and email body. Use proper paragraph spacing (double newlines) and ensure the closing/regards section is clearly separated on its own line.';
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->required(),
            'email_body' => $schema->string()->required(),
        ];
    }
}
