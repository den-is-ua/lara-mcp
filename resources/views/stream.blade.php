<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>AI Streaming</title>
</head>
<body>
    <button id="start">Почати аналіз</button>
    <pre id="output"></pre>

    <script>
        const button = document.getElementById('start');
        const output = document.getElementById('output');

        button.addEventListener('click', async () => {
            output.textContent = '';
            button.disabled = true;

            try {
                const response = await fetch('/stream');

                if (!response.ok || !response.body) {
                    throw new Error('Не вдалося отримати потік');
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();

                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    const events = buffer.split('\n\n');
                    buffer = events.pop() ?? '';

                    for (const event of events) {
                        for (const line of event.split('\n')) {
                            if (!line.startsWith('data: ')) continue;

                            const raw = line.slice(6);

                            if (raw === '[DONE]') continue;

                            const data = JSON.parse(raw);

                            if (data.type === 'text_delta') {
                                output.textContent += data.delta;
                            }
                        }
                    }
                }
            } catch (error) {
                output.textContent += '\nПомилка: ' + error.message;
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
