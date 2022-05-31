package ru.emoo.app.config;

import ru.emoo.app.services.IdProvider;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.ComponentScan;
import org.springframework.context.annotation.Configuration;

@Configuration
@ComponentScan(basePackages = "ru.emoo.app")
public class AppContextConfig {

    @Bean
    public IdProvider idProvider(){
        return new IdProvider();
    }
}
