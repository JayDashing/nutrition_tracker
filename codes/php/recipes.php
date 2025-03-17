<?php
session_start();

//Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: logreg.php");
    exit();
}

$username = $_SESSION['username'];

//Database connection
require_once 'db.php';

// Sample recipes array (this would normally come from a database)
$recipes = [
    [
        'id' => 1,
        'name' => 'Breakfast Berry Smoothie Bowl',
        'calories' => 350,
        'protein' => 12,
        'carbs' => 45,
        'fat' => 14,
        'ingredients' => [
            '1 cup frozen mixed berries',
            '1 banana',
            '1/2 cup Greek yogurt',
            '1/4 cup almond milk',
            '1 tbsp honey',
            '1 tbsp chia seeds',
            'Toppings: sliced fruits, granola, coconut flakes'
        ],
        'instructions' => 'Blend berries, banana, yogurt, and almond milk until smooth. Pour into a bowl and top with fresh fruits, granola, and a drizzle of honey.',
        'image' => '/nutrition_tracker/codes/images/smoothie_bowl.jpg'
    ],
    [
        'id' => 2,
        'name' => 'Grilled Chicken Salad',
        'calories' => 420,
        'protein' => 35,
        'carbs' => 15,
        'fat' => 22,
        'ingredients' => [
            '6 oz grilled chicken breast',
            '2 cups mixed greens',
            '1/2 cucumber, sliced',
            '1 tomato, diced',
            '1/4 red onion, thinly sliced',
            '1/4 cup feta cheese',
            '2 tbsp olive oil',
            '1 tbsp balsamic vinegar',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Grill chicken until fully cooked. Combine all vegetables in a bowl. Slice chicken and add to salad. Drizzle with olive oil and balsamic vinegar. Season with salt and pepper.',
        'image' => '/nutrition_tracker/codes/images/grilled_chicken.jpg'
    ],
    [
        'id' => 3,
        'name' => 'Quinoa Vegetable Stir Fry',
        'calories' => 380,
        'protein' => 14,
        'carbs' => 52,
        'fat' => 16,
        'ingredients' => [
            '1 cup cooked quinoa',
            '1 cup broccoli florets',
            '1 carrot, julienned',
            '1 bell pepper, sliced',
            '1/2 cup snap peas',
            '2 tbsp soy sauce',
            '1 tbsp sesame oil',
            '1 tsp ginger, minced',
            '2 cloves garlic, minced',
            '1 tbsp sesame seeds'
        ],
        'instructions' => 'Cook quinoa according to package instructions. In a wok, heat sesame oil and add garlic and ginger. Add vegetables and stir-fry until tender-crisp. Add cooked quinoa and soy sauce. Toss together and garnish with sesame seeds.',
        'image' => '/nutrition_tracker/codes/images/quinoa_stir.jpg'
    ],
    [
        'id' => 4,
        'name' => 'Greek Yogurt Parfait',
        'calories' => 290,
        'protein' => 18,
        'carbs' => 38,
        'fat' => 8,
        'ingredients' => [
            '1 cup Greek yogurt',
            '1/2 cup mixed berries',
            '1/4 cup granola',
            '1 tbsp honey',
            '1 tsp cinnamon',
            '1 tbsp chopped nuts'
        ],
        'instructions' => 'Layer half of the yogurt in a glass. Add half of the berries and granola. Repeat layers. Drizzle with honey and sprinkle cinnamon and nuts on top.',
        'image' => '/nutrition_tracker/codes/images/greek_yogurt.jpg'
    ],
    [
        'id' => 5,
        'name' => 'Baked Salmon with Asparagus',
        'calories' => 450,
        'protein' => 38,
        'carbs' => 12,
        'fat' => 28,
        'ingredients' => [
            '6 oz salmon fillet',
            '1 bunch asparagus, trimmed',
            '1 lemon, sliced',
            '1 tbsp olive oil',
            '2 cloves garlic, minced',
            'Fresh dill',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Preheat oven to 400°F. Place salmon and asparagus on a baking sheet. Drizzle with olive oil and sprinkle with garlic, salt, and pepper. Top salmon with lemon slices and dill. Bake for 12-15 minutes until salmon is cooked through.',
        'image' => '/nutrition_tracker/codes/images/baked_salmon.jpg'
    ],
    [
        'id' => 6,
        'name' => 'Sweet Potato & Black Bean Bowl',
        'calories' => 380,
        'protein' => 15,
        'carbs' => 65,
        'fat' => 8,
        'ingredients' => [
            '1 medium sweet potato, cubed',
            '1/2 cup black beans, cooked',
            '1/2 cup corn kernels',
            '1/4 cup red onion, diced',
            '1/2 avocado, sliced',
            '1 tbsp lime juice',
            '1 tsp cumin',
            '1/4 tsp chili powder',
            'Fresh cilantro',
            'Salt to taste'
        ],
        'instructions' => 'Roast sweet potato cubes with cumin and chili powder at 425°F for 25 minutes. Combine with black beans, corn, and red onion. Top with avocado slices, lime juice, and cilantro.',
        'image' => '/nutrition_tracker/codes/images/roasted_sweet.jpg'
    ],
    [
        'id' => 7,
        'name' => 'Protein-Packed Overnight Oats',
        'calories' => 340,
        'protein' => 20,
        'carbs' => 42,
        'fat' => 10,
        'ingredients' => [
            '1/2 cup rolled oats',
            '3/4 cup almond milk',
            '1 scoop protein powder',
            '1 tbsp chia seeds',
            '1/2 tbsp maple syrup',
            '1/2 banana, sliced',
            '1 tbsp almond butter',
            'Cinnamon to taste'
        ],
        'instructions' => 'Mix oats, almond milk, protein powder, chia seeds, and maple syrup in a jar. Refrigerate overnight. In the morning, top with banana slices, almond butter, and a sprinkle of cinnamon.',
        'image' => '/nutrition_tracker/codes/images/protein_overnight.jpg'
    ],
    [
        'id' => 8,
        'name' => 'Mediterranean Chickpea Wrap',
        'calories' => 410,
        'protein' => 16,
        'carbs' => 48,
        'fat' => 19,
        'ingredients' => [
            '1 whole wheat tortilla',
            '1/2 cup chickpeas, rinsed and drained',
            '1/4 cup cucumber, diced',
            '1/4 cup cherry tomatoes, halved',
            '1/4 cup red bell pepper, diced',
            '2 tbsp hummus',
            '1 tbsp feta cheese, crumbled',
            '1 tsp olive oil',
            '1/2 tsp lemon juice',
            '1/4 tsp dried oregano',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Mash chickpeas lightly with a fork. Mix with olive oil, lemon juice, oregano, salt, and pepper. Spread hummus on tortilla. Add chickpea mixture, cucumber, tomatoes, bell pepper, and feta. Roll up the wrap and slice in half diagonally.',
        'image' => '/nutrition_tracker/codes/images/chickpea.jpeg'
    ],
    [
        'id' => 9,
        'name' => 'Turkey and Vegetable Stuffed Bell Peppers',
        'calories' => 320,
        'protein' => 28,
        'carbs' => 24,
        'fat' => 12,
        'ingredients' => [
            '2 large bell peppers, halved and seeds removed',
            '8 oz lean ground turkey',
            '1/2 cup quinoa, cooked',
            '1/2 onion, diced',
            '1 zucchini, diced',
            '1 clove garlic, minced',
            '1/2 cup low-sodium marinara sauce',
            '1/4 cup shredded mozzarella cheese',
            '1 tsp Italian seasoning',
            '1 tbsp olive oil',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Preheat oven to 375°F. Heat olive oil in a pan and cook onion and garlic until soft. Add ground turkey and cook until browned. Add zucchini, Italian seasoning, salt, and pepper. Cook for 2-3 minutes. Stir in cooked quinoa and marinara sauce. Fill bell pepper halves with mixture. Top with cheese. Bake for 25-30 minutes until peppers are tender.',
        'image' => '/nutrition_tracker/codes/images/turkey_stuffed.jpg'
    ],
    [
        'id' => 10,
        'name' => 'Mango Coconut Chia Pudding',
        'calories' => 310,
        'protein' => 9,
        'carbs' => 40,
        'fat' => 15,
        'ingredients' => [
            '1/4 cup chia seeds',
            '1 cup coconut milk',
            '1 tbsp honey or maple syrup',
            '1/2 tsp vanilla extract',
            '1 mango, diced',
            '2 tbsp shredded coconut, toasted',
            '1 tbsp sliced almonds',
            'Mint leaves for garnish'
        ],
        'instructions' => 'Mix chia seeds, coconut milk, honey, and vanilla in a bowl. Stir well, then refrigerate for at least 4 hours or overnight. Once set, layer the chia pudding with diced mango in a glass or jar. Top with toasted coconut, sliced almonds, and mint leaves.',
        'image' => '/nutrition_tracker/codes/images/mango_coconut.jpg'
    ],
    [
        'id' => 11,
        'name' => 'Spicy Lentil Power Bowl',
        'calories' => 390,
        'protein' => 22,
        'carbs' => 55,
        'fat' => 10,
        'ingredients' => [
            '1 cup cooked green lentils',
            '1 cup roasted vegetables (sweet potato, bell pepper, zucchini)',
            '1/2 cup spinach, fresh',
            '1/4 avocado, sliced',
            '2 tbsp tahini sauce',
            '1 tsp olive oil',
            '1/2 tsp cumin',
            '1/4 tsp chili flakes',
            '1 tbsp pumpkin seeds',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Cook lentils according to package instructions. Roast vegetables with olive oil, salt, pepper, and cumin at 425°F for 25 minutes. Place spinach in a bowl, top with lentils and roasted vegetables. Add avocado slices, drizzle with tahini sauce, and sprinkle with pumpkin seeds and chili flakes.',
        'image' => '/nutrition_tracker/codes/images/spicy_lentil.jpg'
    ],
    [
        'id' => 12,
        'name' => 'Zucchini Noodles with Pesto Chicken',
        'calories' => 360,
        'protein' => 32,
        'carbs' => 18,
        'fat' => 19,
        'ingredients' => [
            '4 oz grilled chicken breast, sliced',
            '2 medium zucchinis, spiralized',
            '2 tbsp basil pesto (homemade or store-bought)',
            '1/2 cup cherry tomatoes, halved',
            '1 tbsp pine nuts, toasted',
            '1 tbsp grated parmesan cheese',
            '1 tsp olive oil',
            '1 clove garlic, minced',
            'Fresh basil leaves',
            'Salt and pepper to taste'
        ],
        'instructions' => 'Heat olive oil in a pan and sauté garlic until fragrant. Add spiralized zucchini and cook for 2-3 minutes until slightly softened. Toss with pesto sauce. Top with sliced grilled chicken, cherry tomatoes, toasted pine nuts, and parmesan cheese. Garnish with fresh basil leaves.',
        'image' => '/nutrition_tracker/codes/images/zoodles_pesto.jpg'
    ]
];
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipes | NutriTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/nutrition_tracker/codes/css/recipes.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="/nutrition_tracker/codes/images/logoname.png" alt="NutriTrack Logo" style="width: 250px; height: auto;"></div>
</div>

<div class="container">
    <h1>Healthy Recipes</h1>
    <p class="subtitle">Discover nutritious and delicious meals to support your health journey!</p>

    <div class="recipes-grid">
        <?php foreach($recipes as $recipe): ?>
            <div class="recipe-card" data-recipe-id="<?php echo $recipe['id']; ?>">
                <div class="recipe-image">
                    <img src="<?php echo $recipe['image']; ?>" alt="<?php echo htmlspecialchars($recipe['name']); ?>">
                </div>
                <h2 class="recipe-title"><?php echo htmlspecialchars($recipe['name']); ?></h2>
                <div class="recipe-overlay">
                    <div class="recipe-overlay-content">
                        <span>Click to view details</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Modal -->
    <div id="recipe-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div class="modal-body">
                <!-- Content will be dynamically inserted here -->
            </div>
        </div>
    </div>
    
    <a href="home.php" class="btn">Back to Home</a>
</div>

<script>
    // Get the modal
    const modal = document.getElementById('recipe-modal');
    const modalBody = modal.querySelector('.modal-body');
    const closeButton = modal.querySelector('.close-button');
    
    // Recipe cards
    const recipeCards = document.querySelectorAll('.recipe-card');
    
    // Recipe data
    const recipes = <?php echo json_encode($recipes); ?>;
    
    // Open modal when a recipe card is clicked
    recipeCards.forEach(card => {
        card.addEventListener('click', function() {
            const recipeId = this.getAttribute('data-recipe-id');
            const recipe = recipes.find(r => r.id == recipeId);
            
            if (recipe) {
                // Populate modal with recipe details
                let modalContent = `
                    <div class="modal-header">
                        <h2>${recipe.name}</h2>
                    </div>
                    <div class="modal-recipe-image">
                        <img src="${recipe.image}" alt="${recipe.name}">
                    </div>
                    <div class="macro-info">
                        <div class="macro">
                            <span class="macro-value">${recipe.calories}</span>
                            <span class="macro-label">Calories</span>
                        </div>
                        <div class="macro">
                            <span class="macro-value">${recipe.protein}g</span>
                            <span class="macro-label">Protein</span>
                        </div>
                        <div class="macro">
                            <span class="macro-value">${recipe.carbs}g</span>
                            <span class="macro-label">Carbs</span>
                        </div>
                        <div class="macro">
                            <span class="macro-value">${recipe.fat}g</span>
                            <span class="macro-label">Fat</span>
                        </div>
                    </div>
                    <div class="recipe-details">
                        <div class="ingredients">
                            <h3>Ingredients</h3>
                            <ul>
                                ${recipe.ingredients.map(ingredient => `<li>${ingredient}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="instructions">
                            <h3>Instructions</h3>
                            <p>${recipe.instructions}</p>
                        </div>
                    </div>
                `;
                
                modalBody.innerHTML = modalContent;
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
            }
        });
    });
    
    // Close modal when the close button is clicked
    closeButton.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
    });
</script>
</body>
</html>