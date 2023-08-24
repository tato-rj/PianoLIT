<?php

namespace App\Cloudinary;

use App\Performance;

class CloudinarySandbox
{
	public $publicId;

	public function uploadVideo($file, $options)
	{
		$this->publicId = $options['folder'] . '\\' . $options['public_id'];

		return $this;
	}

	public function getPublicId()
	{
		return $this->publicId;
	}

	public function notificationFor(Performance $performance)
	{
		$payload = [
			"notification_type" => "upload",
			"timestamp" => $performance->created_at,
			"request_id" => "71763d4cacf19521f5691a02c8b143b1",
			"asset_id" => "ede59e6d3befdc65a8adc2f381c0f96f",
			"public_id" => $performance->public_id,
			"version" => 1608120578,
			"version_id" => "3144395a27aa6c02df1ca8aaf9aa6e7a",
			"width" => 1279,
			"height" => 853,
			"format" => "mp4",
			"resource_type" => "video",
			"created_at" => "2020-12-16T12:09:38Z",
			"tags" => [],
			"bytes" => 380250,
			"type" => "upload",
			"etag" => "0b40494da087cba7092d29c58aede2e2",
			"placeholder" => false,
			"url" => "http://res.cloudinary.com/demo/image/upload/v1608120578/sample.jpg",
			"secure_url" => "https://res.cloudinary.com/demo/image/upload/v1608120578/sample.jpg",
			"original_filename" => "jeans-1421398-1279x852",
			"notification_context" => [
				"triggered_at" => "2022-11-17T09:07:50.766353Z",
					"triggered_by" => [
					  "source" => "api",
					  "id" => "86787995117726"
					]
				]
			];

		return $payload;
	}
}