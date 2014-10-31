<script>
    $(function() {
        $( "#accordion" ).accordion();
    });
</script>
<header class="header_container">
    <div class="header">
        <div class="header_menu"  id="header_menu">
            <img src="img/menu.png" alt="menu">
        </div>

        <div class="links_explorer">
            <a href="#">Город</a>&nbsp;>&nbsp;<a href="#">Бишкек</a>
        </div>
        <div class="towns_list hidden_mobile" id="hidden">
            <ul>
                <li class="town"><a href="">Айдаркан</a></li>
                <li class="town"><a href="">Балыкчы</a></li>
                <li class="town"><a href="">Баткен</a></li>
                <li class="town"><a href="">Бишкек</a></li>
                <li class="town"><a href="">Жалалабат</a></li>
                <li class="town"><a href="">Исфана</a></li>
                <li class="town"><a href="">Кадамжай</a></li>
                <li class="town"><a href="">Кайыңды</a></li>
                <li class="town"><a href="">Кант</a></li>
                <li class="town"><a href="">Карабалта</a></li>
                <li class="town"><a href="">Каракол</a></li>
                <li class="town"><a href="">Каракөл</a></li>
                <li class="town"><a href="">Карасуу</a></li>
                <li class="town"><a href="">Кемин</a></li>
                <li class="town"><a href="">Кербен</a></li>
                <li class="town"><a href="">Кок-Жангак</a></li>
                <li class="town"><a href="">Кербен</a></li>
                <li class="town"><a href="">Кызылкыя</a></li>
                <li class="town"><a href="">Майлуу-Суу</a></li>
                <li class="town"><a href="">Нарын</a></li>
                <li class="town"><a href="">Ноокат</a></li>
                <li class="town"><a href="">Орловка</a></li>
                <li class="town"><a href="">Ош</a></li>
                <li class="town"><a href="">Сүлүктү</a></li>
                <li class="town"><a href="">Талас</a></li>
                <li class="town"><a href="">Ташкөмүр</a></li>
                <li class="town"><a href="">Токмок</a></li>
                <li class="town"><a href="">Токтогул</a></li>
                <li class="town"><a href="">Өзгөн</a></li>
                <li class="town"><a href="">Чолпоната</a></li>
                <li class="town"><a href="">Шопоков</a></li>
            </ul>
        </div>
    </div>
</header>
<section class="page_container">
    <div class="find_form">
        <div class="find">
            <form action="" method="get">
                <input type="text" placeholder="поиск" class="style_text_field"/>
                <div>
                    <select name="" id="" class="style_list_field">
                        <option value="">Недвижимость</option>
                        <option value="">Сдаю/Аренда</option>
                        <option value="">Авто/Мото</option>
                        <option value="">Электроника</option>
                    </select>
                    <input type="submit" value=">"/>
                </div>
            </form>
        </div>

    </div>
    <div class="login_form">
        <div class="post">
            <a href="#">Подать объявление</a>
        </div>
        <div class="registration_form">
            <a href="#">Войти</a>
        </div>
    </div>
    <div class="links_form">

        <div id="accoridon">
            <h3><a href="#" id="for_sale">Недвижимость</a></h3>
            <div>
                <div class="child_links"  id="for_sale_child_links">
                    <ul>
                        <li><a href="#">Дом</a></li>
                        <li><a href="#">Квартира</a></li>
                        <li><a href="#">Помещение/Участок</a></li>
                    </ul>
                </div>
            </div>
            <h3><a href="#" id="jobs">Сдаю/Аренда</a></h3>
            <div>
                <div class="child_links"  id="jobs_child_links">
                    <ul>
                        <li><a href="#">Дом</a></li>
                        <li><a href="#">Квартира</a></li>
                        <li><a href="#">Помещение/Участок</a></li>
                        <li><a href="#">Одежда/Обувь</a></li>
                        <li><a href="#">Мебель</a></li>
                        <li><a href="#">Животные/Растения</a></li>
                        <li><a href="#">Косметика/Лекарство</a></li>
                        <li><a href="#">Стройматериалы</a></li>
                        <li><a href="#">Бизнес/Организации</a></li>
                    </ul>
                </div>
            </div>
            <h3><a href="#" id="housing">Авто/Мото</a></h3>
            <div>
                <div class="child_links"  id="housing_child_links">
                    <ul>
                        <li><a href="#">Легковые</a></li>
                        <li><a href="#">Грузовые/Спец.техника</a></li>
                        <li><a href="#">Мотоциклы/Велосипеды</a></li>
                    </ul>
                </div>
            </div>
            <h3><a href="#" id="personals">Электроника</a></h3>
            <div>
                <div class="child_links"  id="personals_child_links">
                    <ul>
                        <li><a href="#">Компьютеры</a></li>
                        <li><a href="#">Телефоны</a></li>
                        <li><a href="#">Аудио/Видео/Фото</a></li>
                        <li><a href="#">Телевизоры</a></li>
                        <li><a href="#">Бытовая техника</a></li>
                    </ul>
                </div>
            </div>
            <h3><a href="#" id="community">Общественное</a></h3>
            <div>
                <div class="child_links"  id="community_child_links">
                    <ul>
                        <li>123</li>
                        <li>123</li>
                        <li>123</li>
                        <li>123</li>
                    </ul>
                </div>
            </div>
            <h3><a href="#" id="services">Сервисы</a></h3>
            <div>
                <div class="child_links"  id="services_child_links">
                    <ul>
                        <li><a href="#">Авто/Мото/Вело</a></li>
                        <li><a href="#">Интернет/Web Design</a></li>
                        <li><a href="#">Мобильная связь</a></li>
                        <li><a href="#">Образование</a></li>
                        <li><a href="#">Путешествие/Отдых</a></li>
                        <li><a href="#">Красота/Здоровье</a></li>
                        <li><a href="#">Бухгалтерские услуги</a></li>
                        <li><a href="#">Сантехника/Электрика</a></li>
                        <li><a href="#">Ремонт</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<footer class="footer_container">
    <ul>
        <li>&copy; 2014</li>
        <li><a href="#">Помощь</a></li>
        <li><a href="#">О нас</a></li>
        <li><a href="<?php print $this->link(array('m'=>0))?>">desktop</a></li>
    </ul>
</footer>
<script src="js/load_sublings.js"></script>
