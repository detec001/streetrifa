<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database functions
require_once '../admin/process_admin_login.php';

try {
    // Get active raffles from database
    $sql = "SELECT 
                r.id,
                r.name,
                r.description,
                r.draw_date,
                r.ticket_price,
                r.total_tickets,
                COALESCE(r.sold_tickets, 0) as sold_tickets,
                r.commission_rate,
                r.images,
                r.status,
                r.created_at,
                a.username as created_by_name
            FROM raffles r 
            LEFT JOIN admins a ON r.created_by = a.id 
            WHERE r.status = 'active' 
            AND r.draw_date > NOW()
            ORDER BY r.draw_date ASC";
    
    $raffles = fetchAll($sql);
    
    // Process raffles data for frontend
    $processed_raffles = [];
    
    foreach ($raffles as $raffle) {
        // Decode images and create full path
        $images = json_decode($raffle['images'], true) ?: [];
        $main_image = 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=150&h=150&fit=crop&crop=center'; // Default fallback
        
        if (!empty($images)) {
            // Check if image file exists and create proper URL
            $image_path = '../uploads/raffles/' . $images[0];
            if (file_exists($image_path)) {
                // Create URL relative to the web root
                $main_image = './uploads/raffles/' . $images[0];
            }
        }
        
        // Format date
        $draw_date = new DateTime($raffle['draw_date']);
        $now = new DateTime();
        $diff = $now->diff($draw_date);
        
        // Calculate days remaining
        $days_remaining = $diff->days;
        if ($draw_date < $now) {
            $days_remaining = 0;
        }
        
        // Format date for display
        $formatted_date = $draw_date->format('d M • H:i');
        
        // Calculate availability
        $available_tickets = $raffle['total_tickets'] - $raffle['sold_tickets'];
        $progress_percentage = $raffle['total_tickets'] > 0 ? ($raffle['sold_tickets'] / $raffle['total_tickets']) * 100 : 0;
        
        $processed_raffles[] = [
            'id' => $raffle['id'],
            'name' => $raffle['name'],
            'description' => $raffle['description'],
            'date' => $formatted_date,
            'draw_date' => $raffle['draw_date'],
            'price' => '$' . number_format($raffle['ticket_price'], 2),
            'price_value' => floatval($raffle['ticket_price']),
            'image' => $main_image,
            'total_tickets' => intval($raffle['total_tickets']),
            'sold_tickets' => intval($raffle['sold_tickets']),
            'available_tickets' => $available_tickets,
            'progress_percentage' => round($progress_percentage, 1),
            'commission_rate' => floatval($raffle['commission_rate']),
            'status' => $raffle['status'],
            'days_remaining' => $days_remaining,
            'is_available' => $available_tickets > 0,
            'created_by' => $raffle['created_by_name'] ?: 'Sistema'
        ];
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'data' => $processed_raffles,
        'count' => count($processed_raffles),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Error en get_raffles.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener las rifas',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>