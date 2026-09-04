<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptExecutionController extends Controller
{
    public function index(): View
    {
        return view('executions.index', $this->pageData());
    }

    public function run(): RedirectResponse
    {
        return back()->with('status', 'Queued fix-url-keys for migration #92831.');
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(): array
    {
        return [
            'migration' => [
                'id' => 92831,
                'source' => 'Magento 1.9',
                'target' => 'Shopify Plus',
                'status' => 'Active',
                'repository' => 'cart2cart-migration-scripts',
                'repository_url' => 'https://github.com',
                'run_script' => 'fix-url-keys',
            ],
            'executions' => [
                [
                    'script' => 'fix-url-keys',
                    'id' => 'execution-18452',
                    'status' => 'completed',
                    'processed' => 5000,
                    'total' => 5000,
                    'commit' => '8f32a91',
                    'requested_by' => 'Vitalii M.',
                    'initials' => 'VM',
                    'avatar' => 'teal',
                    'started' => 'Sep 3, 09:41',
                    'duration' => '1m 16s',
                ],
                [
                    'script' => 'remap-categories',
                    'id' => 'execution-18441',
                    'status' => 'failed',
                    'processed' => 1180,
                    'total' => 5000,
                    'commit' => '8f32a91',
                    'requested_by' => 'Serhii K.',
                    'initials' => 'SK',
                    'avatar' => 'amber',
                    'started' => 'Sep 3, 08:12',
                    'duration' => '42s',
                ],
                [
                    'script' => 'rebuild-redirects',
                    'id' => 'execution-18420',
                    'status' => 'completed',
                    'processed' => 5000,
                    'total' => 5000,
                    'commit' => '3c91e04',
                    'requested_by' => 'AI Agent',
                    'initials' => 'AA',
                    'avatar' => 'violet',
                    'started' => 'Sep 2, 18:04',
                    'duration' => '3m 02s',
                ],
                [
                    'script' => 'fix-url-keys',
                    'id' => 'execution-18388',
                    'status' => 'cancelled',
                    'processed' => 640,
                    'total' => 5000,
                    'commit' => '3c91e04',
                    'requested_by' => 'Vitalii M.',
                    'initials' => 'VM',
                    'avatar' => 'teal',
                    'started' => 'Sep 2, 16:51',
                    'duration' => '11s',
                ],
            ],
        ];
    }
}
