const TOKEN_CHAVE = 'lumora.token';

export class ErroApi extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly erros?: Record<string, string[]>,
    ) {
        super(message);
    }
}

export function obterToken(): string | null {
    return localStorage.getItem(TOKEN_CHAVE);
}

export function salvarToken(token: string): void {
    localStorage.setItem(TOKEN_CHAVE, token);
}

export function removerToken(): void {
    localStorage.removeItem(TOKEN_CHAVE);
}

export async function requisitar<T>(
    caminho: string,
    opcoes: RequestInit = {},
): Promise<T> {
    const token = obterToken();
    const resposta = await fetch(caminho, {
        ...opcoes,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...opcoes.headers,
        },
    });

    if (resposta.status === 204) {
        return undefined as T;
    }

    const corpo = await resposta.json();

    if (!resposta.ok) {
        const primeiraMensagem =
            Object.values(corpo.errors ?? {}).flat()[0] ??
            corpo.message ??
            corpo.mensagem ??
            'Não foi possível concluir a solicitação.';
        throw new ErroApi(String(primeiraMensagem), resposta.status, corpo.errors);
    }

    return corpo as T;
}
