import fs from 'node:fs';
import http from 'node:http';
import https from 'node:https';

const pfxPath = process.env.BA_HTTPS_PFX;
const passphrase = process.env.BA_HTTPS_PASSWORD;
const httpsPort = Number(process.env.BA_HTTPS_PORT || 8443);
const targetPort = Number(process.env.BA_HTTP_PORT || 8000);

if (!pfxPath || !passphrase) {
    throw new Error('Faltan BA_HTTPS_PFX o BA_HTTPS_PASSWORD.');
}

https.createServer(
    { pfx: fs.readFileSync(pfxPath), passphrase },
    (request, response) => {
        const proxyRequest = http.request(
            {
                hostname: '127.0.0.1',
                port: targetPort,
                method: request.method,
                path: request.url,
                headers: {
                    ...request.headers,
                    host: request.headers.host || `127.0.0.1:${httpsPort}`,
                    'x-forwarded-for': request.socket.remoteAddress || '',
                    'x-forwarded-port': String(httpsPort),
                    'x-forwarded-proto': 'https',
                },
            },
            (proxyResponse) => {
                response.writeHead(proxyResponse.statusCode || 502, proxyResponse.headers);
                proxyResponse.pipe(response);
            },
        );

        proxyRequest.on('error', (error) => {
            if (!response.headersSent) {
                response.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' });
            }

            response.end(`No se pudo conectar con Laravel: ${error.message}`);
        });

        request.pipe(proxyRequest);
    },
).listen(httpsPort, '0.0.0.0', () => {
    console.log(`Proxy HTTPS disponible en 0.0.0.0:${httpsPort}`);
});
