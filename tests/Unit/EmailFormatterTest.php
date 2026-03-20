<?php

namespace Tests\Unit;

use App\Utils\EmailFormatter;
use Tests\TestCase;

class EmailFormatterTest extends TestCase
{
    public function test_it_formats_email_correctly(): void
    {
        $input = "Dear Business Name Team, \n\n I hope this email finds you well.   I’m reaching out to explore a potential collaboration opportunity with your business. \n\n We’ve been closely following your presence in the industry space and are impressed by the value you provide to your customers. We specialize in helping businesses like yours generate high-quality leads and improve customer acquisition through data-driven strategies. \n\n Our approach focuses on increasing visibility, improving conversions, and delivering measurable growth. We’ve successfully worked with similar businesses to boost their outreach and bring in more targeted customers. \n\n I’d love to schedule a quick 10-minute call to discuss how we can support your growth goals. \n\n Best regards, \n The Team";

        $formatted = EmailFormatter::format($input);

        // Check if greeting is on its own line
        $this->assertStringStartsWith('Dear Business Name Team,', $formatted);

        // Check if paragraphs are separated by double newlines
        $this->assertStringContainsString('I hope this email finds you well.', $formatted);
        $this->assertStringContainsString("Best regards,\n\nThe Team", $formatted);

        // Check no double spaces
        $this->assertStringNotContainsString('  ', $formatted);
    }

    public function test_it_removes_extra_line_breaks_inside_paragraphs(): void
    {
        $input = "This is a paragraph\nwith an internal\nline break.";
        $expected = 'This is a paragraph with an internal line break.';
        $this->assertEquals($expected, EmailFormatter::format($input));
    }

    public function test_it_splits_long_paragraphs(): void
    {
        $input = 'Sentence one. Sentence two. Sentence three. Sentence four.';
        $formatted = EmailFormatter::format($input);

        // Should be split into two paragraphs with 2 sentences each
        $this->assertStringContainsString("Sentence one. Sentence two.\n\nSentence three. Sentence four.", $formatted);
    }
}
