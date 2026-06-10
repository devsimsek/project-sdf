# Tutorial: File Upload

Validate and store file uploads securely in SDF.

---

## 1. Route

```php
<?php
// app/config/routes.php

$config['/upload']      = ['UploadController/form',   'GET'];
$config['/upload']      = ['UploadController/store',  'POST'];
$config['/files/{url}'] = ['UploadController/serve',  'GET'];
```

---

## 2. Controller

`app/controllers/UploadController.php`:

```php
<?php

use SDF\Controller;
use SDF\Http\UploadedFile;

class UploadController extends Controller
{
    private const UPLOAD_DIR  = 'storage/uploads/';
    private const MAX_SIZE    = 5 * 1024 * 1024;  // 5 MB
    private const ALLOWED     = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function form(): void
    {
        $this->fuse->render('upload/form');
    }

    public function store(): void
    {
        // Use PSR-7 UploadedFile from fromGlobals()
        $psrRequest  = \SDF\Http\ServerRequest::fromGlobals();
        $uploaded    = $psrRequest->getUploadedFiles()['file'] ?? null;

        if (!$uploaded || $uploaded->getError() !== UPLOAD_ERR_OK) {
            $this->response->status(400)->json(['error' => 'No file uploaded']);
            return;
        }

        // Validate size
        if ($uploaded->getSize() > self::MAX_SIZE) {
            $this->response->status(413)->json(['error' => 'File too large (max 5 MB)']);
            return;
        }

        // Validate MIME — use finfo, not $_FILES['type'] (spoofable)
        $stream   = $uploaded->getStream();
        $tmpPath  = stream_get_meta_data($stream->detach())['uri'] ?? null;

        if (!$tmpPath) {
            $this->response->status(500)->json(['error' => 'Unable to read upload']);
            return;
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);

        if (!in_array($mimeType, self::ALLOWED, true)) {
            $this->response->status(415)->json([
                'error'   => 'Unsupported file type',
                'allowed' => self::ALLOWED,
            ]);
            return;
        }

        // Build safe filename
        $ext      = pathinfo($uploaded->getClientFilename(), PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $dest     = self::UPLOAD_DIR . $filename;

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $uploaded->moveTo($dest);

        $this->response->status(201)->json([
            'file' => $filename,
            'url'  => '/files/' . $filename,
            'size' => $uploaded->getSize(),
            'type' => $mimeType,
        ]);
    }

    public function serve(string $filename): void
    {
        // Sanitise — no path traversal
        $filename = basename($filename);
        $path     = self::UPLOAD_DIR . $filename;

        if (!file_exists($path)) {
            $this->response->status(404)->text('File not found');
            return;
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=86400');
        readfile($path);
    }
}
```

> **Legacy approach:** You can still use `$_FILES` directly for quick scripts. The PSR-7 `UploadedFileInterface` (`$request->getUploadedFiles()`) provides a testable, framework-agnostic alternative.

---

## 3. Upload Form View

`app/views/upload/form.php`:

```html
<!doctype html>
<html lang="en">
<head><title>Upload File</title></head>
<body>
  <h1>Upload a File</h1>
  <form method="POST" action="/upload" enctype="multipart/form-data">
    <input type="file" name="file" accept="image/*,.pdf" required>
    <button type="submit">Upload</button>
  </form>
  <p><small>Max 5 MB. Accepted: JPEG, PNG, WebP, PDF.</small></p>
</body>
</html>
```

---

## 4. Storage Directory

Add to `.gitignore` to avoid committing uploaded files:

```
/storage/uploads/
```

---

## Security Checklist

| Risk | Mitigation |
|---|---|
| MIME spoofing | Use `finfo` on actual file bytes, not `$_FILES['type']` |
| Path traversal | `basename()` strips directory components |
| Executable upload | Randomised hex filename removes original extension context |
| Disk fill | `MAX_SIZE` constant enforced before `move_uploaded_file` |

---

## What You Learned

- Validating uploads with `finfo` (safe) vs `$_FILES['type']` (unsafe)
- Generating unpredictable filenames with `bin2hex(random_bytes())`
- Serving files with correct Content-Type headers
- Preventing path traversal with `basename()`
- Using PSR-7 `UploadedFileInterface` via `ServerRequest::fromGlobals()`
