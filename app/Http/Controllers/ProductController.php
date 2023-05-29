<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Filter products by category
        $category = $request->query('category');
        $products = $category
            ? Product::where('category', $category)->get()
            : Product::all();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $product = Product::create($request->all());

        // Inform external systems via webhooks
        $this->broadcastProductChanges('product_created', $product);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());

        // Inform external systems via webhooks
        $this->broadcastProductChanges('product_updated', $product);

        return response()->json($product);
    }

    public function getAvailableTags()
    {
        // Get unique tags from all products
        $tags = Product::pluck('tags')->flatten()->unique();

        return response()->json($tags);
    }

    private function broadcastProductChanges($action, $product)
    {
        //There I would put the actual webhook URLs of the external systems
        $externalSystems = [
            //'http://example.com/webhook1',
            //'http://example.com/webhook2',
            // Add more webhook URLs here
        ];

        // Create a Guzzle HTTP client
        $client = new Client();

        foreach ($externalSystems as $url) {
            $payload = [
                'action' => $action,
                'product' => $product,
            ];

            try {
                // Send the payload to the external system's webhook URL
                $response = $client->post($url, ['json' => $payload]);

                 $statusCode = $response->getStatusCode();
                 $responseData = $response->getBody()->getContents();
            } catch (RequestException $e) {
                 $statusCode = $e->getResponse()->getStatusCode();
                 $errorMessage = $e->getMessage();
            }
        }
    }
}
