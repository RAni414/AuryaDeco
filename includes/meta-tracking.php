<?php
/** Meta credentials belong in server environment variables, never in HTML. */
function meta_tracking_events(array $context): array
{
    $events = [['event_name' => 'PageView', 'event_id' => bin2hex(random_bytes(16)), 'custom_data' => new stdClass()]];
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($page === 'produit.php' && !empty($context['p']['id'])) {
        $events[] = ['event_name'=>'ViewContent', 'event_id'=>bin2hex(random_bytes(16)), 'custom_data'=>['content_ids'=>[(string)$context['p']['id']], 'content_type'=>'product', 'content_name'=>$context['p']['name']]];
    }
    if ($page === 'commande.php' && !empty($_SESSION['cart'])) {
        $events[] = ['event_name'=>'InitiateCheckout', 'event_id'=>bin2hex(random_bytes(16)), 'custom_data'=>new stdClass()];
    }
    if ($page === 'merci.php' && !empty($context['order']['number']) && $context['order']['number'] === ($_SESSION['last_order'] ?? null)) {
        $order = $context['order'];
        $events[] = ['event_name'=>'Purchase', 'event_id'=>'purchase-' . $order['number'], 'custom_data'=>['currency'=>'TND', 'value'=>(float)$order['total'], 'order_id'=>$order['number']]];
    }
    return $events;
}

function meta_send_events(array $events): bool
{
    $token = trim((string)getenv('META_CAPI_TOKEN'));
    if ($token === '' || !function_exists('curl_init')) return false;
    $user = ['client_ip_address'=>$_SERVER['REMOTE_ADDR'] ?? '', 'client_user_agent'=>$_SERVER['HTTP_USER_AGENT'] ?? ''];
    foreach (['_fbp'=>'fbp', '_fbc'=>'fbc'] as $cookie=>$field) {
        if (is_string($_COOKIE[$cookie] ?? null) && preg_match('/^fb\.[0-9]+\.[0-9]+\.[A-Za-z0-9_.-]{1,250}$/', $_COOKIE[$cookie])) $user[$field] = $_COOKIE[$cookie];
    }
    $batch = [];
    foreach ($events as $event) {
        if ($event['event_name'] === 'Purchase' && isset($_SESSION['meta_sent'][$event['event_id']])) continue;
        $event['event_time'] = time();
        $event['action_source'] = 'website';
        // Do not forward search strings, customer details or authentication URLs.
        $event['event_source_url'] = 'https://auryadeco.tn/' . rawurlencode(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        $event['user_data'] = $user;
        $batch[] = $event;
    }
    if (!$batch) return true;
    $body = ['data'=>$batch, 'access_token'=>$token];
    $test = trim((string)getenv('META_TEST_EVENT_CODE'));
    if ($test !== '') $body['test_event_code'] = $test;
    $ch = curl_init('https://graph.facebook.com/v25.0/1731109547867871/events');
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($body), CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT_MS=>600, CURLOPT_TIMEOUT_MS=>1500]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = is_string($response) ? json_decode($response, true) : null;
    $ok = $status === 200 && !empty($result['events_received']);
    if ($ok) foreach ($batch as $event) if ($event['event_name'] === 'Purchase') $_SESSION['meta_sent'][$event['event_id']] = true;
    if (!$ok) error_log('Meta Conversions API: event delivery failed (HTTP ' . (int)$status . ').');
    return $ok;
}
