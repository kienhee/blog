package com.kienhee.blog;

import com.kienhee.blog.config.TrashProperties;
import com.kienhee.blog.service.TrashService;
import com.kienhee.blog.service.impl.TrashPurgeJob;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.config.YamlPropertiesFactoryBean;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;
import org.springframework.core.io.support.PathMatchingResourcePatternResolver;

import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.Properties;
import java.util.Set;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.Mockito.*;

/** Production must never receive demo content or the demo admin account; retention sweeps stay opt-in. */
@DisplayName("Deployment safety (unit)")
class DemoDataSeparationTests {

    private static final Pattern DEMO_INSERT =
            Pattern.compile("(?i)insert\\s+into\\s+(users|posts|categories|hashtags|comments)\\b");

    @Test
    @DisplayName("schema migrations contain no demo content; the demo seeds live in db/demo")
    void demoSeedsAreSeparate() throws Exception {
        PathMatchingResourcePatternResolver resolver = new PathMatchingResourcePatternResolver();
        for (Resource migration : resolver.getResources("classpath*:db/migration/*.sql")) {
            String sql = new String(migration.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
            assertFalse(DEMO_INSERT.matcher(sql).find(), migration.getFilename() + " inserts demo content; move it to db/demo");
        }
        Set<String> demo = Arrays.stream(resolver.getResources("classpath*:db/demo/*.sql"))
                .map(Resource::getFilename).collect(Collectors.toSet());
        assertTrue(demo.contains("V4__seed_categories_and_hashtags.sql"));
        assertTrue(demo.contains("V6__seed_posts.sql"));
    }

    @Test
    @DisplayName("dev loads db/demo; the prod profile does not, and turns retention sweeps on")
    void profiles() {
        Properties dev = yaml("application.yaml");
        assertTrue(dev.getProperty("spring.flyway.locations").contains("classpath:db/demo"));
        assertEquals("false", dev.getProperty("app.trash.auto-purge-enabled"));

        Properties prod = yaml("application-prod.yaml");
        assertEquals("classpath:db/migration", prod.getProperty("spring.flyway.locations"));
        assertEquals("true", prod.getProperty("app.trash.auto-purge-enabled"));
        assertEquals("true", prod.getProperty("app.media.trash.auto-purge-enabled"));
        assertEquals("true", prod.getProperty("spring.thymeleaf.cache"));
    }

    @Test
    @DisplayName("the trash sweep does nothing unless enabled, then purges with the configured retention")
    void trashSweep() {
        TrashService service = mock(TrashService.class);
        TrashProperties properties = new TrashProperties();
        new TrashPurgeJob(service, properties).sweep();
        verifyNoInteractions(service);

        properties.setAutoPurgeEnabled(true);
        properties.setRetentionDays(14);
        new TrashPurgeJob(service, properties).sweep();
        verify(service).purgeExpired(14);
    }

    private static Properties yaml(String name) {
        YamlPropertiesFactoryBean factory = new YamlPropertiesFactoryBean();
        factory.setResources(new ClassPathResource(name));
        Properties properties = factory.getObject();
        assertNotNull(properties, name);
        return properties;
    }
}
