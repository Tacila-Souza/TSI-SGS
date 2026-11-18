export class ApiError extends Error {
  constructor(
    message: string,
    public readonly codigo: number,
    public readonly erros?: Record<string, string>,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export interface Envelope<T> {
  sucesso: boolean
  dados?: T
  mensagem?: string
  erros?: Record<string, string>
}

const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:8000'

async function request<T>(caminho: string, init?: RequestInit): Promise<Envelope<T>> {
  let resposta: Response

  try {
    resposta = await fetch(`${BASE_URL}${caminho}`, {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        ...(init?.headers ?? {}),
      },
    })
  } catch {
    throw new ApiError('Não foi possível conectar à API.', 0)
  }

  let corpo: Envelope<T>
  try {
    corpo = (await resposta.json()) as Envelope<T>
  } catch {
    throw new ApiError('Resposta inválida da API.', resposta.status)
  }

  if (!resposta.ok || !corpo.sucesso) {
    throw new ApiError(
      corpo.mensagem ?? 'Erro ao processar a solicitação.',
      resposta.status,
      corpo.erros,
    )
  }

  return corpo
}

export const api = {
  get: <T>(caminho: string) => request<T>(caminho),
  post: <T>(caminho: string, corpo: unknown) =>
    request<T>(caminho, { method: 'POST', body: JSON.stringify(corpo) }),
  put: <T>(caminho: string, corpo: unknown) =>
    request<T>(caminho, { method: 'PUT', body: JSON.stringify(corpo) }),
  del: <T>(caminho: string) => request<T>(caminho, { method: 'DELETE' }),
}
