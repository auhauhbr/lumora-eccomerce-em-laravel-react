<?php

namespace BancoDeDados\Semeadores;

use App\Modelos\Categoria;
use App\Modelos\Marca;
use App\Modelos\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SemeadorCatalogo extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Rede' => [
                [
                    'Ponto De Acesso Ubiquiti UniFi U6+ Wi-Fi 6 Interno',
                    'Ubiquiti',
                    899.00,
                    8,
                    'Access point Wi-Fi 6 para ambientes internos, com gerenciamento centralizado e ótima estabilidade.',
                    [
                        'https://http2.mlstatic.com/D_NQ_NP_2X_981270-MLA106570259363_022026-F.webp',
                        'https://http2.mlstatic.com/D_Q_NP_819862-MLA108910014806_032026-R.webp',
                        'https://http2.mlstatic.com/D_NQ_NP_2X_649993-MLA106569785775_022026-F.webp',
                        'https://http2.mlstatic.com/D_Q_NP_639078-MLA106568772905_022026-R.webp',
                    ],
                ],
            ],
            'Smart Home' => [
                [
                    'Câmera Intelbras Wi-Fi Full HD',
                    'Intelbras',
                    249.00,
                    18,
                    'Câmera residencial Wi-Fi para monitoramento remoto, instalação simples e imagem Full HD.',
                    [
                        'https://backend.intelbras.com/sites/default/files/styles/medium/public/2020-08/IM3%20-%20Frontal-min_0.png',
                        'https://backend.intelbras.com/sites/default/files/styles/medium/public/2020-08/IM3%20-%20Direita%20parede-min.png',
                        'https://backend.intelbras.com/sites/default/files/styles/medium/public/2020-08/IM3%20-%20Traseira-min.png',
                        'https://backend.intelbras.com/sites/default/files/styles/medium/public/2020-08/IM3%20-%20Perfil%20Esquerda-min.png',
                    ],
                ],
            ],
            'Acessórios' => [
                [
                    'Carregador USB-C GaN 100W',
                    'Intelbras',
                    299.90,
                    22,
                    'Carregador compacto com tecnologia GaN para notebook, tablet e celular em alta potência.',
                    [
                        'https://intelbras.vtexassets.com/arquivos/ids/175069-800-auto?v=638981316443300000&width=800&height=auto&aspect=true',
                        'https://intelbras.vtexassets.com/arquivos/ids/175072-800-auto?v=638981316443600000&width=800&height=auto&aspect=true',
                        'https://intelbras.vtexassets.com/arquivos/ids/175073-800-auto?v=638981316443600000&width=800&height=auto&aspect=true',
                        'https://intelbras.vtexassets.com/arquivos/ids/175076-800-auto?v=638981316443770000&width=800&height=auto&aspect=true',
                    ],
                ],
            ],
            'Componentes' => [
                [
                    'Memória Corsair Vengeance 32GB DDR5',
                    'Corsair',
                    799.00,
                    11,
                    'Kit DDR5 de alta frequência para computadores modernos, multitarefa pesada e setups gamers.',
                    [
                        'https://images4.kabum.com.br/produtos/fotos/506124/memoria-corsair-vengeance-32gb-2x16gb-6000mhz-ddr5-c36-preto-cmk32gx5m2e6000c36_1703857836_gg.jpg',
                        'https://images4.kabum.com.br/produtos/fotos/506124/memoria-corsair-vengeance-32gb-2x16gb-6000mhz-ddr5-c36-preto-cmk32gx5m2e6000c36_1703857835_gg.jpg',
                        'https://images4.kabum.com.br/produtos/fotos/506124/memoria-corsair-vengeance-32gb-2x16gb-6000mhz-ddr5-c36-preto-cmk32gx5m2e6000c36_1703857834_gg.jpg',
                        'https://images4.kabum.com.br/produtos/fotos/506124/memoria-corsair-vengeance-32gb-2x16gb-6000mhz-ddr5-c36-preto-cmk32gx5m2e6000c36_1703857837_gg.jpg',
                    ],
                ],
                [
                    'Placa de Vídeo RTX 4060 8GB',
                    'Gigabyte',
                    2199.90,
                    9,
                    'Placa de vídeo atual para jogos, edição e aceleração em fluxos criativos.',
                    [
                        'https://media.pichau.com.br/media/catalog/product/cache/2f958555330323e505eba7ce930bdf27/g/v/gv-n4060wf2oc-8gd1.jpg',
                        'https://media.pichau.com.br/media/catalog/product/cache/2f958555330323e505eba7ce930bdf27/g/v/gv-n4060wf2oc-8gd6.jpg',
                        'https://media.pichau.com.br/media/catalog/product/cache/2f958555330323e505eba7ce930bdf27/g/v/gv-n4060wf2oc-8gd3.jpg',
                        'https://media.pichau.com.br/media/catalog/product/cache/2f958555330323e505eba7ce930bdf27/g/v/gv-n4060wf2oc-8gd5.jpg',
                    ],
                ],
            ],
            'Monitores' => [
                [
                    'Monitor Samsung Odyssey 24" 144Hz',
                    'Samsung',
                    1099.00,
                    14,
                    'Monitor gamer curvo com 144Hz para navegação fluida e jogos competitivos.',
                    [
                        'https://images.samsung.com/is/image/samsung/p6pim/br/lc24rg50fzlmzd/gallery/br-c24rg5-lc24rg50fzlmzd-550347351_10150117933342?$1164_776_PNG$',
                        'https://images.samsung.com/is/image/samsung/p6pim/br/lc24rg50fzlmzd/gallery/br-gaming-lc24rg50fzlmzd-back-black-thumb-536810424?$64_64_PNG$',
                        'https://images.samsung.com/is/image/samsung/p6pim/br/lc24rg50fzlmzd/gallery/br-gaming-lc24rg50fzlmzd-dynamic--black-thumb-536810431?$64_64_PNG$',
                        'https://images.samsung.com/is/image/samsung/p6pim/br/lc24rg50fzlmzd/gallery/br-gaming-lc24rg50fzlmzd-r-side-black-536810442?$Q90_1368_1094_F_JPG$',
                    ],
                ],
            ],
            'Notebooks' => [
                [
                    'Notebook Acer Nitro RTX 3050 16GB 512GB',
                    'Acer',
                    5899.00,
                    6,
                    'Notebook gamer com RTX 3050, tela Full HD 144Hz e desempenho para jogos e criação.',
                    [
                        'https://images5.kabum.com.br/produtos/fotos/617805/notebook-gamer-acer-nitro-v15-intel-core-i7-13620h-16gb-ram-rtx-3050-ssd-512gb-15-6-full-hd-ips-144hz-linux-preto-anv15-51-73e9_1722888806_gg.jpg',
                        'https://images5.kabum.com.br/produtos/fotos/617805/notebook-gamer-acer-nitro-v15-intel-core-i7-13620h-16gb-ram-rtx-3050-ssd-512gb-15-6-full-hd-ips-144hz-linux-preto-anv15-51-73e9_1722888804_gg.jpg',
                        'https://images5.kabum.com.br/produtos/fotos/617805/notebook-gamer-acer-nitro-v15-intel-core-i7-13620h-16gb-ram-rtx-3050-ssd-512gb-15-6-full-hd-ips-144hz-linux-preto-anv15-51-73e9_1722888805_gg.jpg',
                        'https://images5.kabum.com.br/produtos/fotos/617805/notebook-gamer-acer-nitro-v15-intel-core-i7-13620h-16gb-ram-rtx-3050-ssd-512gb-15-6-full-hd-ips-144hz-linux-preto-anv15-51-73e9_1722888808_gg.jpg',
                    ],
                ],
            ],
        ];

        $slugsAtivos = [];
        $categoriasAtivas = [];

        foreach ($catalogo as $nomeCategoria => $produtos) {
            $categoriasAtivas[] = Str::slug($nomeCategoria);

            $categoria = Categoria::query()->updateOrCreate([
                'slug' => Str::slug($nomeCategoria),
            ], [
                'name' => $nomeCategoria,
                'description' => "Produtos selecionados da categoria {$nomeCategoria}.",
                'is_active' => true,
            ]);

            foreach ($produtos as [$nome, $marca, $preco, $estoque, $descricao, $imagens]) {
                Marca::query()->updateOrCreate([
                    'slug' => Str::slug($marca),
                ], [
                    'name' => $marca,
                    'description' => "Marca {$marca} disponível no catálogo Lumora.",
                    'is_active' => true,
                ]);

                $slug = Str::slug($nome);
                $slugsAtivos[] = $slug;

                Produto::query()->updateOrCreate([
                    'slug' => $slug,
                ], [
                    'category_id' => $categoria->id,
                    'name' => $nome,
                    'description' => $descricao,
                    'brand' => $marca,
                    'condition' => 'novo',
                    'price' => $preco,
                    'stock' => $estoque,
                    'image_url' => $imagens[0],
                    'image_urls' => $imagens,
                    'is_active' => true,
                ]);
            }
        }

        // Sem foto real por enquanto? Melhor não poluir a vitrine.
        Produto::query()
            ->whereNotIn('slug', $slugsAtivos)
            ->update(['is_active' => false]);

        Categoria::query()
            ->whereNotIn('slug', $categoriasAtivas)
            ->update(['is_active' => false]);
    }
}
