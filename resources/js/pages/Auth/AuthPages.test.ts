import { mount, flushPromises } from "@vue/test-utils";
import { reactive } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";
import LoginPage from "./LoginPage.vue";
import RegisterPage from "./RegisterPage.vue";
import ResetPassword from "./ResetPassword.vue";

const mocks = vi.hoisted(() => ({
    useForm: vi.fn(),
    usePage: vi.fn(),
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: { template: "<span />" },
    Link: {
        props: ["href"],
        template: '<a :href="href"><slot /></a>',
    },
    useForm: mocks.useForm,
    usePage: mocks.usePage,
}));

type PostOptions = {
    onError?: () => void;
};

describe("authentication forms", () => {
    beforeEach(() => {
        const form = reactive({
            email: "",
            token: "test-token",
            password: "",
            password_confirmation: "",
            errors: {} as Record<string, string>,
            processing: false,
            post: vi.fn((url: string, options: PostOptions) => {
                expect(url).toMatch(/^\/(login|register|reset-password)$/);
                form.errors.email = "Échec simulé côté serveur.";
                options.onError?.();
            }),
        });

        mocks.useForm.mockReturnValue(form);
        mocks.usePage.mockReturnValue({ props: { flash: {} } });
    });

    it("clears the password after a login error and keeps the email", async () => {
        const wrapper = mount(LoginPage, {
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#email").setValue("student@example.test");
        await wrapper.get("#password").setValue("sensitive-password");
        await wrapper.get("form").trigger("submit");
        await flushPromises();

        expect(wrapper.get<HTMLInputElement>("#email").element.value).toBe(
            "student@example.test",
        );
        expect(wrapper.get<HTMLInputElement>("#password").element.value).toBe(
            "",
        );
    });

    it("toggles login password visibility without changing the password", async () => {
        const wrapper = mount(LoginPage, {
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#password").setValue("correct-password");
        await wrapper
            .get('[aria-label="Afficher le mot de passe"]')
            .trigger("click");

        expect(wrapper.get<HTMLInputElement>("#password").element.type).toBe(
            "text",
        );
        expect(wrapper.get<HTMLInputElement>("#password").element.value).toBe(
            "correct-password",
        );

        await wrapper
            .get('[aria-label="Masquer le mot de passe"]')
            .trigger("click");
        expect(wrapper.get<HTMLInputElement>("#password").element.type).toBe(
            "password",
        );
        expect(
            mocks.useForm.mock.results.at(-1)?.value.post,
        ).not.toHaveBeenCalled();
    });

    it("clears registration passwords after an error and keeps the account details", async () => {
        const wrapper = mount(RegisterPage, {
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#name").setValue("Tounde D.");
        await wrapper.get("#email").setValue("toundedy@gmail.com");
        await wrapper.get("#password").setValue("sensitive-password-value");
        await wrapper
            .get("#password_confirmation")
            .setValue("sensitive-password-value");
        await wrapper.get("form").trigger("submit");
        await flushPromises();

        expect(wrapper.get<HTMLInputElement>("#name").element.value).toBe(
            "Tounde D.",
        );
        expect(wrapper.get<HTMLInputElement>("#email").element.value).toBe(
            "toundedy@gmail.com",
        );
        expect(wrapper.get<HTMLInputElement>("#password").element.value).toBe(
            "",
        );
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .value,
        ).toBe("");
    });

    it("toggles registration passwords independently without changing their values", async () => {
        const wrapper = mount(RegisterPage, {
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#password").setValue("new-password-value");
        await wrapper
            .get("#password_confirmation")
            .setValue("new-password-value");

        await wrapper
            .get('[aria-label="Afficher le mot de passe"]')
            .trigger("click");
        expect(wrapper.get<HTMLInputElement>("#password").element.type).toBe(
            "text",
        );
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .type,
        ).toBe("password");

        await wrapper
            .get('[aria-label="Afficher la confirmation du mot de passe"]')
            .trigger("click");
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .type,
        ).toBe("text");
        expect(wrapper.get<HTMLInputElement>("#password").element.value).toBe(
            "new-password-value",
        );
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .value,
        ).toBe("new-password-value");
        expect(
            mocks.useForm.mock.results.at(-1)?.value.post,
        ).not.toHaveBeenCalled();
    });

    it("clears both password fields after a reset error", async () => {
        const wrapper = mount(ResetPassword, {
            props: {
                token: "test-token",
                email: "student@example.test",
            },
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#email").setValue("student@example.test");
        await wrapper.get("#password").setValue("sensitive-password");
        await wrapper
            .get("#password_confirmation")
            .setValue("sensitive-password");
        await wrapper.get("form").trigger("submit");
        await flushPromises();

        expect(wrapper.get<HTMLInputElement>("#email").element.value).toBe(
            "student@example.test",
        );
        expect(wrapper.get<HTMLInputElement>("#password").element.value).toBe(
            "",
        );
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .value,
        ).toBe("");
    });

    it("toggles reset password fields independently without changing their values", async () => {
        const wrapper = mount(ResetPassword, {
            props: {
                token: "test-token",
                email: "student@example.test",
            },
            global: {
                stubs: {
                    AuthLayout: {
                        props: ["title", "description"],
                        template: "<main><slot /></main>",
                    },
                },
            },
        });

        await wrapper.get("#password").setValue("new-password-value");
        await wrapper
            .get("#password_confirmation")
            .setValue("new-password-value");
        await wrapper
            .get('[aria-label="Afficher le nouveau mot de passe"]')
            .trigger("click");

        expect(wrapper.get<HTMLInputElement>("#password").element.type).toBe(
            "text",
        );
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .type,
        ).toBe("password");

        await wrapper
            .get('[aria-label="Afficher la confirmation du mot de passe"]')
            .trigger("click");
        expect(
            wrapper.get<HTMLInputElement>("#password_confirmation").element
                .type,
        ).toBe("text");
        expect(
            mocks.useForm.mock.results.at(-1)?.value.post,
        ).not.toHaveBeenCalled();
    });
});
