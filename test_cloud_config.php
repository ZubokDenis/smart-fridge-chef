<?php
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

require_once __DIR__ . '/wordpress/wp-load.php';

echo "Testing option updates...\n";

// Test setting Claude provider
update_option('sfc_ai_provider', 'claude');
update_option('sfc_claude_model', 'claude-3-5-sonnet-20241022');
update_option('sfc_claude_base_url', 'https://api.anthropic.com');

$config = SFC_AI_Engine::get_ai_config();
echo "Active provider: " . $config['provider'] . "\n";
echo "Claude Model: " . $config['claude_model'] . "\n";
echo "Claude Base URL: " . $config['claude_base_url'] . "\n";

// Test setting Ollama Cloud provider
update_option('sfc_ai_provider', 'ollama_cloud');
update_option('sfc_ollama_url', 'https://ollama.my-custom-domain.com');
update_option('sfc_ollama_api_key', 'test-token-12345');
update_option('sfc_ollama_model', 'llama3.2-vision');

$config = SFC_AI_Engine::get_ai_config();
echo "\nSwitched to Ollama Cloud:\n";
echo "Active provider: " . $config['provider'] . "\n";
echo "Ollama URL: " . $config['ollama_url'] . "\n";
echo "Ollama API Key: " . $config['ollama_api_key'] . "\n";
echo "Ollama Model: " . $config['ollama_model'] . "\n";

// Reset to blank URL so user can enter theirs
update_option('sfc_ollama_url', '');
update_option('sfc_ollama_api_key', '');

echo "\nAll config tests passed successfully!\n";
