<?php
// Shared page content partials.
// Each function renders the inner HTML for a page using $data passed from the controller.

function bp_page_home(array $data): string
{
    $sliders = $data['sliders'] ?? [];
    $products = $data['products'] ?? [];
    $testimonials = $data['testimonials'] ?? [];
    $clients = $data['clients'] ?? [];
    $news = $data['news'] ?? [];
    ob_start();
    ?>
    <h1>Welcome to <?php echo htmlspecialchars(($data['settings']['company_name'] ?? 'BizProfile')); ?></h1>
    <?php if ($sliders): ?>
        <section class="card-grid">
            <?php foreach ($sliders as $s): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($s['title']); ?></h3>
                    <p><?php echo htmlspecialchars($s['subtitle'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <h2 style="margin-top:2rem;">Featured Products</h2>
    <section class="card-grid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <h3><a href="/products/<?php echo htmlspecialchars($p['slug']); ?>"><?php echo htmlspecialchars($p['name']); ?></a></h3>
                <p><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 120)); ?></p>
            </div>
        <?php endforeach; ?>
    </section>
    <h2 style="margin-top:2rem;">Latest News</h2>
    <section class="card-grid">
        <?php foreach ($news as $n): ?>
            <div class="card">
                <h3><a href="/blog/<?php echo htmlspecialchars($n['slug']); ?>"><?php echo htmlspecialchars($n['title']); ?></a></h3>
                <p><?php echo htmlspecialchars(substr(strip_tags($n['content'] ?? ''), 0, 100)); ?></p>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}

function bp_page_generic(array $data, string $title, string $bodyHtml): string
{
    return "<h1>" . htmlspecialchars($title) . "</h1>" . $bodyHtml;
}

function bp_page_products(array $data): string
{
    $products = $data['products'] ?? [];
    $categories = $data['categories'] ?? [];
    ob_start();
    ?>
    <h1>Products</h1>
    <section class="card-grid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <h3><a href="/products/<?php echo htmlspecialchars($p['slug']); ?>"><?php echo htmlspecialchars($p['name']); ?></a></h3>
                <p><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 120)); ?></p>
                <?php if (!empty($p['price'])): ?><p><strong>Price: $<?php echo htmlspecialchars($p['price']); ?></strong></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}

function bp_page_product_detail(array $data): string
{
    $product = $data['product'] ?? [];
    $images = $data['images'] ?? [];
    $attributes = $data['attributes'] ?? [];
    ob_start();
    ?>
    <h1><?php echo htmlspecialchars($product['name'] ?? ''); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
    <?php if (!empty($product['price'])): ?><p><strong>Price: $<?php echo htmlspecialchars($product['price']); ?></strong></p><?php endif; ?>
    <?php if ($images): ?>
        <h3>Gallery</h3>
        <section class="card-grid">
            <?php foreach ($images as $img): ?>
                <div class="card"><img src="/storage/uploads/<?php echo htmlspecialchars($img['image']); ?>" alt="" style="max-width:100%;"></div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <?php if ($attributes): ?>
        <h3>Specifications</h3>
        <ul><?php foreach ($attributes as $a): ?><li><strong><?php echo htmlspecialchars($a['attribute_name']); ?>:</strong> <?php echo htmlspecialchars($a['attribute_value']); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function bp_page_news(array $data): string
{
    $news = $data['news'] ?? [];
    ob_start();
    ?><h1>News & Announcements</h1><section class="card-grid"><?php
    foreach ($news as $n):
        ?><div class="card"><h3><a href="/blog/<?php echo htmlspecialchars($n['slug']); ?>"><?php echo htmlspecialchars($n['title']); ?></a></h3><p><?php echo htmlspecialchars(substr(strip_tags($n['content'] ?? ''), 0, 100)); ?></p></div><?php
    endforeach;
    ?></section><?php
    return ob_get_clean();
}

function bp_page_news_detail(array $data): string
{
    $article = $data['article'] ?? [];
    return "<h1>" . htmlspecialchars($article['title'] ?? '') . "</h1><div>" . nl2br(htmlspecialchars($article['content'] ?? '')) . "</div>";
}

function bp_page_releases(array $data): string
{
    $releases = $data['releases'] ?? [];
    ob_start();
    ?><h1>Product Releases</h1><section class="card-grid"><?php
    foreach ($releases as $r):
        ?><div class="card"><h3><?php echo htmlspecialchars($r['title']); ?> <?php if (!empty($r['version'])): ?><small>v<?php echo htmlspecialchars($r['version']); ?></small><?php endif; ?></h3><p><?php echo htmlspecialchars(substr(strip_tags($r['content'] ?? ''), 0, 120)); ?></p><?php if (!empty($r['download_file'])): ?><p><a class="btn" href="/product-releases/download/<?php echo $r['id']; ?>">Download</a></p><?php endif; ?></div><?php
    endforeach;
    ?></section><?php
    return ob_get_clean();
}

function bp_page_contact(array $data): string
{
    ob_start();
    ?>
    <h1>Contact Us</h1>
    <?php if (!empty($_SESSION['success'])): ?><p style="color:green;"><?php echo htmlspecialchars($_SESSION['success']); ?></p><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><p style="color:red;"><?php echo htmlspecialchars($_SESSION['error']); ?></p><?php endif; ?>
    <form method="POST" action="/contact">
        <?php echo csrf_field(); ?>
        <p><input name="name" placeholder="Name" required style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><input name="email" type="email" placeholder="Email" required style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><input name="subject" placeholder="Subject" required style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><textarea name="message" placeholder="Message" required style="padding:.5rem;width:100%;max-width:400px;"></textarea></p>
        <p><button class="btn" type="submit">Send Message</button></p>
    </form>
    <?php
    return ob_get_clean();
}

function bp_page_products_services(array $data): string
{
    $products = $data['products'] ?? [];
    $services = $data['services'] ?? [];
    ob_start();
    ?>
    <h1>Products & Services</h1>
    <h2>Products</h2>
    <section class="card-grid">
        <?php foreach ($products as $p): ?><div class="card"><h3><?php echo htmlspecialchars($p['name']); ?></h3><p><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 100)); ?></p></div><?php endforeach; ?>
    </section>
    <h2>Services</h2>
    <section class="card-grid">
        <?php foreach ($services as $s): ?><div class="card"><h3><?php echo htmlspecialchars($s['name']); ?></h3><p><?php echo htmlspecialchars(substr($s['description'] ?? '', 0, 100)); ?></p></div><?php endforeach; ?>
    </section>
    <h2>Request a Demo</h2>
    <form method="POST" action="/demo-request">
        <?php echo csrf_field(); ?>
        <p><input name="name" placeholder="Name" required style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><input name="email" type="email" placeholder="Email" required style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><input name="product_interest" placeholder="Product of interest" style="padding:.5rem;width:100%;max-width:400px;"></p>
        <p><textarea name="message" placeholder="Message" style="padding:.5rem;width:100%;max-width:400px;"></textarea></p>
        <p><button class="btn" type="submit">Request Demo</button></p>
    </form>
    <?php
    return ob_get_clean();
}

function bp_page_simple(array $data, string $title, string $body): string
{
    return "<h1>" . htmlspecialchars($title) . "</h1>" . $body;
}
