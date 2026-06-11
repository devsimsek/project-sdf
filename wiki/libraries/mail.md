# Mail

SDF provides a mail facade with pluggable drivers: `LogMailer` (default, writes to file) and `SmtpMailer` (sends via PHP's `mail()`).

## Configuration

Configure the mail driver in `app/config/mail.php`:

```php
$config['mail'] = [
    'default' => 'log', // log or smtp
    'from' => ['address' => 'hello@example.com', 'name' => 'SDF'],
    'smtp' => [
        'host' => getenv('MAIL_HOST') ?: 'localhost',
        'port' => (int)(getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    ],
    'log' => [
        'path' => sys_get_temp_dir() . '/sdf_mail.log',
    ],
];
```

## Mailable Class

Build an email with the fluent `Mailable` API:

```php
use SDF\Mail\Mailable;

$mailable = (new Mailable())
    ->to('user@example.com')
    ->cc('admin@example.com')
    ->bcc('audit@example.com')
    ->from('noreply@example.com', 'SDF')
    ->subject('Welcome!')
    ->body('<h1>Hello</h1>')
    ->attach('/path/to/invoice.pdf');
```

| Method | Description |
|---|---|
| `to(string $address, ?string $name)` | Add a To recipient |
| `cc(string $address, ?string $name)` | Add a CC recipient |
| `bcc(string $address, ?string $name)` | Add a BCC recipient |
| `from(string $address, ?string $name)` | Set the sender |
| `subject(string $subject)` | Set the subject |
| `body(string $body)` | Set the raw HTML body |
| `attach(string $filePath)` | Attach a file |

## Mail Facade

`Mail::to()` creates a `Mailable`, `Mail::send()` dispatches it:

```php
use SDF\Mail\Mail;

$mailable = Mail::to('user@example.com')
    ->subject('Welcome!')
    ->body('<h1>Hello</h1>');
Mail::send($mailable);
```

The `from` address is automatically populated from config if not set on the mailable.

## Mailer Interface

Both drivers implement `SDF\Mail\Mailer`:

```php
namespace SDF\Mail;

interface Mailer
{
    public function send(Mailable $mailable): bool;
}
```

### LogMailer

The default driver. Writes every email to a log file (`sys_get_temp_dir()/sdf_mail.log` by default). Each entry includes timestamp, sender, recipients, subject, body, and attachments.

### SmtpMailer

Sends via PHP's built-in `mail()` function with full MIME headers. Supports CC, BCC, and multipart attachments with base64 encoding.

```php
// Runtime override
$mail = Mail::getInstance();
$mail->setMailer(new SmtpMailer([
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user',
    'password' => 'pass',
    'encryption' => 'tls',
]));
```
