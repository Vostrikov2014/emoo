package ru.emoo.app.services;

import ru.emoo.web.dto.Book;
import org.springframework.beans.BeansException;
import org.springframework.beans.factory.DisposableBean;
import org.springframework.beans.factory.InitializingBean;
import org.springframework.beans.factory.config.BeanPostProcessor;

import javax.annotation.PostConstruct;
import javax.annotation.PreDestroy;
import java.util.logging.Logger;

public class IdProvider implements InitializingBean, DisposableBean, BeanPostProcessor {

    Logger logger = Logger.getLogger(String.valueOf(IdProvider.class));

    public String providerId(Book book) {
        return this.hashCode() + "_" + book.hashCode();
    }

    private void initIdProvider() {
        logger.info("provider INIT");
    }

    private void destroyIdProvider() {
        logger.info("provider DESTROY");
    }

    private void defaultInit(){
        logger.info("default INIT");
    }

    private void defaultDestroy(){
        logger.info("default DESTROY");
    }

    @Override
    public void afterPropertiesSet() throws Exception {
        logger.info("provider afterPropertiesSet invoked");
    }

    @Override
    public void destroy() throws Exception {
        logger.info("DisposableBean destroy invoked");
    }

    @Override
    public Object postProcessAfterInitialization(Object bean, String beanName) throws BeansException {
        logger.info("postProcessAfterInitialization invoked by bean"+beanName);
        return null;
    }

    @Override
    public Object postProcessBeforeInitialization(Object bean, String beanName) throws BeansException {
        logger.info("postProcessBeforeInitialization invoked by bean"+beanName);
        return null;
    }

    @PostConstruct
    public void postConstructIdProvider() {
        logger.info("PostConstruct annotated method called");
    }

    @PreDestroy
    public void preDestroyIdProvider() {
        logger.info("PreDestroy annotated method called");
    }
}
