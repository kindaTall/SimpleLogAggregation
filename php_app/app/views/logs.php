<!DOCTYPE html>
<html>
<head>
    <title>Logs</title>
</head>
<body>
    <h1>Logs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Host</th>
                <th>Host Process</th>
                <th>Log Level</th>
                <th>Log Message</th>
                <th>Timestamp</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= $log['id'] ?></td>
                <td><?= $log['host'] ?></td>
                <td><?= $log['host_process'] ?></td>
                <td><?= $log['log_level'] ?></td>
                <td><?= $log['log_message'] ?></td>
                <td><?= $log['timestamp'] ?></td>
                <td><?= $log['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
