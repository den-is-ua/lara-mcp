

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ToDo AI Chat</title>
    @vite(['resources/js/app.js'])

    <style>
        body {
            font-family: sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 16px;
        }

        #messages {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            min-height: 300px;
            margin-bottom: 16px;
        }

        .message {
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .user {
            color: #155eef;
        }

        .assistant {
            color: #222;
        }

        form {
            display: flex;
            gap: 8px;
        }

        input {
            flex: 1;
            padding: 12px;
        }

        button {
            padding: 12px 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>ToDo AI Chat</h1>

    <div id="messages"></div>

    <form id="chat-form">
        <input
            id="message"
            type="text"
            placeholder="Напиши повідомлення..."
            autocomplete="off"
            required
        >
        <button id="send" type="submit">Надіслати</button>
    </form>

    <script type="module">
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message');
        const sendButton = document.getElementById('send');
        const messagesContainer = document.getElementById('messages');
        const chatUrl = @json(route('chat.message'));

        const messages = [];

        function renderMessage(role, text) {
            const element = document.createElement('div');
            element.className = `message ${role}`;
            element.textContent = `${role === 'user' ? 'Ти' : 'AI'}: ${text}`;

            messagesContainer.appendChild(element);

            return element;
        }

        function payloadFrom(data) {
            if (typeof data === 'string') {
                try {
                    return JSON.parse(data);
                } catch {
                    return null;
                }
            }

            return data;
        }

        function waitForSubscription(channel) {
            return new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Не вдалося підключитися до трансляції.'));
                }, 10000);

                channel.subscribed(() => {
                    clearTimeout(timeout);
                    resolve();
                });

                channel.error(() => {
                    clearTimeout(timeout);
                    reject(new Error('Підписка на канал відхилена.'));
                });
            });
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const text = input.value.trim();

            if (!text) {
                return;
            }

            if (!window.Echo) {
                renderMessage('assistant', 'Помилка: трансляція не підключена.');

                return;
            }

            messages.push({
                id: crypto.randomUUID(),
                role: 'user',
                parts: [{ type: 'text', text }],
            });

            renderMessage('user', text);

            input.value = '';
            input.disabled = true;
            sendButton.disabled = true;

            const channelId = crypto.randomUUID();
            let channelName = null;
            let assistantElement = null;

            try {
                channelName = `chat.${channelId}`;
                const channel = window.Echo.channel(channelName);
                assistantElement = renderMessage('assistant', '…');
                let assistantText = '';
                let streamFinished = false;

                const streamResult = new Promise((resolve) => {
                    channel.listenToAll((eventName, data) => {
                        const payload = payloadFrom(data);

                        if (!payload || typeof payload !== 'object') {
                            return;
                        }

                        if (payload.type === 'text_delta') {
                            assistantText += payload.delta ?? '';
                            assistantElement.textContent = `AI: ${assistantText}`;

                            return;
                        }

                        if (payload.type === 'stream_end') {
                            streamFinished = true;
                            resolve(assistantText);
                        }
                    });
                });

                await waitForSubscription(channel);

                const response = await fetch(chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .content,
                    },
                    body: JSON.stringify({
                        channel: channelId,
                        messages,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${await response.text()}`);
                }

                const reply = streamFinished
                    ? assistantText
                    : await Promise.race([
                        streamResult,
                        new Promise((resolve, reject) => {
                            setTimeout(() => reject(new Error('Відповідь не надійшла.')), 15000);
                        }),
                    ]);

                messages.push({
                    id: crypto.randomUUID(),
                    role: 'assistant',
                    parts: [{ type: 'text', text: reply }],
                });
            } catch (error) {
                const errorText = `Помилка: ${error.message}`;

                if (assistantElement) {
                    assistantElement.textContent = errorText;
                } else {
                    renderMessage('assistant', errorText);
                }

                console.error(error);
            } finally {
                if (channelName) {
                    window.Echo.leave(channelName);
                }

                input.disabled = false;
                sendButton.disabled = false;
                input.focus();
            }
        });
    </script>
</body>
</html>
