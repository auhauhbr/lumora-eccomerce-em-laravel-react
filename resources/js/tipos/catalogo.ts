export type Categoria = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active?: boolean;
    produtos_count?: number;
};

export type Marca = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    products_count?: number;
};

export type Produto = {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    description: string | null;
    brand: string | null;
    condition: 'novo' | 'usado' | 'recondicionado';
    price: string;
    stock: number;
    image_url: string | null;
    image_urls: string[] | null;
    is_active: boolean;
    categoria: Categoria;
};

export type ItemCarrinho = {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: string;
    produto: Produto;
};

export type Carrinho = {
    id: number;
    itens: ItemCarrinho[];
};

export type Usuario = {
    id: number;
    name: string;
    email: string;
    role: 'customer' | 'admin';
};

export type Endereco = {
    id: number;
    zip_code: string;
    street: string;
    number: string;
    complement: string | null;
    neighborhood: string;
    city: string;
    state: string;
    created_at?: string;
};

export type ItemPedido = {
    id: number;
    product_id: number;
    product_name: string;
    unit_price: string;
    quantity: number;
    total: string;
};

export type Pedido = {
    id: number;
    address_id: number;
    status: string;
    payment_status: string;
    payment_provider: string | null;
    payment_url: string | null;
    subtotal: string;
    shipping_value: string;
    total: string;
    endereco: Endereco;
    itens: ItemPedido[];
    created_at?: string;
    usuario?: Pick<Usuario, 'id' | 'name' | 'email'>;
};
