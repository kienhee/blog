package com.kienhee.blog;

import com.kienhee.blog.repository.SiteSettingRepository;
import com.kienhee.blog.service.SettingService;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.HashMap;
import java.util.Map;
import java.util.Optional;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.content;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/** Footer social links come from Settings; unsafe or empty values render nothing. Settings are restored after. */
@SpringBootTest
@DisplayName("Footer social links")
class FooterSocialLinksTests {

    private static final String[] KEYS = {"social.x", "social.youtube"};

    @Autowired private WebApplicationContext context;
    @Autowired private SettingService settingService;
    @Autowired private SiteSettingRepository siteSettingRepository;

    private MockMvc mockMvc;
    private final Map<String, Optional<String>> originals = new HashMap<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        for (String key : KEYS) {
            originals.put(key, settingService.get(key));
        }
    }

    @AfterEach
    void restore() {
        originals.forEach((key, value) -> {
            if (value.isPresent()) {
                settingService.saveAll(Map.of(key, value.get()));
            } else {
                siteSettingRepository.deleteById(key);
            }
        });
    }

    @Test
    @DisplayName("an https link is shown; a javascript: value is never rendered")
    void rendersOnlySafeLinks() throws Exception {
        settingService.saveAll(Map.of("social.x", "https://x.com/kienhee", "social.youtube", "javascript:alert(1)"));
        mockMvc.perform(get("/"))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("href=\"https://x.com/kienhee\"")))
                .andExpect(content().string(containsString("Elsewhere")))
                .andExpect(content().string(not(containsString("javascript:alert"))))
                .andExpect(content().string(not(containsString(">YouTube<"))))
                .andExpect(content().string(not(containsString("href=\"#\""))));
    }

    @Test
    @DisplayName("with no links set, the Elsewhere column disappears")
    void hiddenWhenEmpty() throws Exception {
        settingService.saveAll(Map.of("social.x", "", "social.youtube", ""));
        mockMvc.perform(get("/"))
                .andExpect(status().isOk())
                .andExpect(content().string(not(containsString("Elsewhere"))));
    }
}
