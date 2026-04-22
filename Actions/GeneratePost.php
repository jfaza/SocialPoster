<?php

namespace JavidFazaeli\SocialPoster\Actions;

use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;

class GeneratePost extends AbstractRoute
{
    public function process()
    {
        if (! ee('Request')->isPost()) {
            return $this->json(['success' => false, 'message' => 'POST required.'], 405);
        }

        if ((int) ee()->session->userdata('member_id') < 1 || ! ee('Permission')->has('can_access_cp')) {
            return $this->json(['success' => false, 'message' => 'Control Panel access required.'], 403);
        }

        $prompt = trim((string) ee()->input->post('prompt', true));
        if ($prompt === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a prompt.'], 422);
        }

        try {
            $result = ee('socialposter:generator')->generate($prompt, [
                'template_id' => (int) ee()->input->post('template_id'),
                'image_brief' => ee()->input->post('image_brief', false),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function json(array $payload, int $status)
    {
        ee()->output->set_status_header($status);
        ee()->output->set_header('Content-Type: application/json; charset=UTF-8');
        ee()->output->set_output(json_encode($payload));
        return;
    }
}
