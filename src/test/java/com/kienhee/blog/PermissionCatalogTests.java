package com.kienhee.blog;

import com.kienhee.blog.entity.Permission;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.repository.PermissionRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.context.ApplicationContext;
import org.springframework.core.io.Resource;
import org.springframework.core.io.support.PathMatchingResourcePatternResolver;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.util.ClassUtils;
import org.springframework.web.context.WebApplicationContext;

import java.lang.reflect.Method;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.List;
import java.util.Set;
import java.util.TreeSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * The seeded permission catalog (V20) matches what the code checks, and the two seeded roles
 * (admin, user) hold what they should. These guard against a permission check being added in code
 * without a row to grant it, or a row that nothing checks.
 */
@SpringBootTest
@DisplayName("Permission catalog and seeded roles")
class PermissionCatalogTests {

    private static final Set<String> CATALOG = Set.of(
            "dashboard:view",
            "posts:view", "posts:create", "posts:edit", "posts:publish", "posts:delete",
            "categories:view", "categories:create", "categories:edit", "categories:delete",
            "hashtags:view", "hashtags:create", "hashtags:edit", "hashtags:delete",
            "media:view", "media:create", "media:edit", "media:delete", "media:purge",
            "comments:view", "comments:edit", "comments:delete",
            "users:view", "users:create", "users:edit", "users:delete",
            "roles:view", "roles:create", "roles:edit", "roles:delete",
            "settings:view", "settings:edit",
            "subscribers:view", "subscribers:send", "subscribers:delete");

    private static final Set<String> USER_ROLE = Set.of(
            "dashboard:view", "posts:view", "posts:create", "posts:edit",
            "categories:view", "hashtags:view", "media:view", "media:create", "comments:view");

    private static final Pattern CODE = Pattern.compile("'([a-z]+:[a-z]+)'");

    @Autowired private PermissionRepository permissionRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private ApplicationContext applicationContext;
    @Autowired private WebApplicationContext context;

    private MockMvc mockMvc;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
    }

    private Set<String> dbCodes() {
        return permissionRepository.findAll().stream().map(Permission::getCode).collect(Collectors.toCollection(TreeSet::new));
    }

    @Test
    @DisplayName("the database holds exactly the permissions of the catalog")
    void catalogSeeded() {
        assertEquals(new TreeSet<>(CATALOG), dbCodes());
        assertEquals(new TreeSet<>(CATALOG), new TreeSet<>(Arrays.asList(TestAuth.ALL_PERMISSIONS)),
                "TestAuth.ALL_PERMISSIONS must mirror the catalog");
        for (Permission p : permissionRepository.findAll()) {
            assertEquals(p.getResource() + ":" + p.getAction(), p.getCode());
            assertFalse(p.getLabel().isBlank());
        }
    }

    @Test
    @DisplayName("admin is a system role with every permission; the old roles are gone")
    void adminRole() {
        Role admin = roleRepository.findWithPermissionsById(roleRepository.findBySlug("admin").orElseThrow().getId()).orElseThrow();
        assertTrue(admin.isSystemRole());
        assertEquals("Admin", admin.getName());
        assertEquals(new TreeSet<>(CATALOG),
                admin.getPermissions().stream().map(Permission::getCode).collect(Collectors.toCollection(TreeSet::new)));
        for (String retired : List.of("owner", "editor", "author", "viewer")) {
            assertTrue(roleRepository.findBySlug(retired).isEmpty(), retired + " should no longer exist");
        }
    }

    @Test
    @DisplayName("user is an editable contributor role")
    void userRole() {
        Role user = roleRepository.findWithPermissionsById(roleRepository.findBySlug("user").orElseThrow().getId()).orElseThrow();
        assertFalse(user.isSystemRole());
        assertEquals(new TreeSet<>(USER_ROLE),
                user.getPermissions().stream().map(Permission::getCode).collect(Collectors.toCollection(TreeSet::new)));
    }

    @Test
    @DisplayName("every permission checked by a controller exists, and every permission is checked somewhere")
    void codeAndCatalogAgree() throws Exception {
        Set<String> checked = new TreeSet<>();
        for (Object bean : applicationContext.getBeansWithAnnotation(Controller.class).values()) {
            Class<?> type = ClassUtils.getUserClass(bean);
            collect(type.getAnnotation(PreAuthorize.class), checked);
            for (Method method : type.getDeclaredMethods()) {
                collect(method.getAnnotation(PreAuthorize.class), checked);
            }
        }

        Set<String> inTemplates = new TreeSet<>();
        Pattern perms = Pattern.compile("perms\\.contains\\('([a-z]+:[a-z]+)'\\)");
        for (Resource template : new PathMatchingResourcePatternResolver().getResources("classpath*:templates/**/*.html")) {
            Matcher m = perms.matcher(new String(template.getInputStream().readAllBytes(), StandardCharsets.UTF_8));
            while (m.find()) inTemplates.add(m.group(1));
        }

        Set<String> db = dbCodes();
        Set<String> missing = new TreeSet<>(checked);
        missing.addAll(inTemplates);
        missing.removeAll(db);
        assertTrue(missing.isEmpty(), "checked in code/templates but not seeded: " + missing);

        Set<String> unused = new TreeSet<>(db);
        unused.removeAll(checked);
        unused.removeAll(inTemplates);
        unused.remove("posts:publish"); // checked programmatically in PostController (and shown in post-new.html)
        assertTrue(unused.isEmpty(), "seeded but never checked: " + unused);
    }

    @Test
    @DisplayName("the Roles page lists every permission for a role")
    void rolesPageShowsCatalog() throws Exception {
        Long adminId = roleRepository.findBySlug("admin").orElseThrow().getId();
        String html = mockMvc.perform(get("/admin/roles").param("roleId", adminId.toString()).with(TestAuth.owner()).with(TestLocale.en()))
                .andExpect(status().isOk())
                .andReturn().getResponse().getContentAsString();
        assertEquals(CATALOG.size(), html.split("name=\"permissionIds\"", -1).length - 1);
        assertTrue(html.contains("Publish, schedule or archive posts"));
        assertTrue(html.contains("Delete permanently and empty the trash"));
    }

    @Test
    @DisplayName("Roles needs roles:* now, not users:*")
    void rolesUseTheirOwnPermissions() throws Exception {
        mockMvc.perform(get("/admin/roles").with(TestAuth.withPermissions("u@test.com", "users:view")))
                .andExpect(status().isForbidden());
        mockMvc.perform(get("/admin/roles").with(TestAuth.withPermissions("r@test.com", "roles:view")))
                .andExpect(status().isOk());
        mockMvc.perform(get("/admin/dashboard").with(TestAuth.withPermissions("p@test.com", "posts:view")))
                .andExpect(status().isForbidden());
    }

    private static void collect(PreAuthorize annotation, Set<String> into) {
        if (annotation == null) return;
        Matcher m = CODE.matcher(annotation.value());
        while (m.find()) into.add(m.group(1));
    }
}
