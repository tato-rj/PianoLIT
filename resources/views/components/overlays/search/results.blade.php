<div class="text-center mb-4">We found {{$posts->count()}} {{str_plural('result', $posts->count())}}</div>
@each('components.blog.cards.horizontal', $posts, 'post')