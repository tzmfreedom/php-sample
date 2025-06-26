{* @var string $title *}
{* @var string $message *}
{* @var array $users *}
{* @var int $count *}

<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    
    {% if $count > 0 %}
        <h2>Users ({{ $count }})</h2>
        <ul>
            {% for $users as $user %}
                <li>{{ $user['name'] }} - {{ $user['email'] }}</li>
            {% endfor %}
        </ul>
    {% else %}
        <p>No users found.</p>
    {% endif %}
</body>
</html>